<?php

namespace App\Services\AI;

use App\Models\ActivityLog;
use App\Models\AIGeneration;
use App\Models\AIProvider;
use App\Models\Brand;
use App\Models\Content;
use App\Models\PromptTemplate;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use App\Models\SocialPost;
use App\Models\SocialPostMedia;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIContentService
{
    public function __construct(
        protected AIProviderManager $providerManager,
        protected PromptService $promptService,
        protected HumanizationService $humanizationService,
    ) {}

    /**
     * Generate platform-specific social post variants for a brand and content item.
     *
     * @param  array<int, int>  $platformIds
     * @return Collection<int, SocialPost>
     */
    public function generatePostsForContent(
        Brand $brand,
        Content $content,
        array $platformIds,
        ?AIProvider $providerModel = null,
        bool $submitForApproval = true
    ): Collection {
        $brand->loadMissing(['brandSetting', 'brandVoiceSetting']);
        $content->loadMissing('contentMedia');
        $providerModel = $providerModel ?? $this->providerManager->resolveDefaultModel();
        $provider = $this->providerManager->resolve($providerModel);

        $platforms = SocialPlatform::query()->whereIn('id', $platformIds)->get();
        $createdPosts = collect();

        foreach ($platforms as $platform) {
            $promptData = $this->promptService->buildPrompts(
                purpose: PromptTemplate::PURPOSE_CONTENT_GENERATION,
                brand: $brand,
                content: $content,
                platform: $platform,
            );

            try {
                $result = $provider->generate(
                    systemPrompt: $promptData['system_prompt'],
                    userPrompt: $promptData['user_prompt'],
                    options: $promptData['variables'],
                );

                $rawDraft = $result['text'];

                $humanizedBody = $this->humanizationService->humanize(
                    draft: $rawDraft,
                    brand: $brand,
                    sourceContent: $content,
                    platform: $platform,
                );

                $generation = AIGeneration::create([
                    'brand_id' => $brand->id,
                    'content_id' => $content->id,
                    'social_platform_id' => $platform->id,
                    'ai_provider_id' => $providerModel->id,
                    'prompt_template_id' => $promptData['template']?->id,
                    'prompt_version' => $promptData['version'],
                    'generation_type' => AIGeneration::TYPE_CONTENT_GENERATION,
                    'input' => $promptData['user_prompt'],
                    'output' => $humanizedBody,
                    'model' => $result['model'],
                    'input_tokens' => $result['input_tokens'],
                    'output_tokens' => $result['output_tokens'],
                    'estimated_cost' => $result['estimated_cost'],
                    'status' => AIGeneration::STATUS_COMPLETED,
                ]);
            } catch (\Throwable $e) {
                AIGeneration::create([
                    'brand_id' => $brand->id,
                    'content_id' => $content->id,
                    'social_platform_id' => $platform->id,
                    'ai_provider_id' => $providerModel->id,
                    'prompt_template_id' => $promptData['template']?->id,
                    'prompt_version' => $promptData['version'],
                    'generation_type' => AIGeneration::TYPE_CONTENT_GENERATION,
                    'input' => $promptData['user_prompt'],
                    'output' => null,
                    'model' => $providerModel->model,
                    'status' => AIGeneration::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);

                OperationalAlertNotification::notifyAdmins(
                    alertType: 'ai_provider_unavailable',
                    title: "AI Provider Error: {$providerModel->name}",
                    message: $e->getMessage(),
                    severity: 'danger',
                    actionUrl: route('admin.ai.providers.index'),
                    deduplicationKey: "ai_provider_error:{$providerModel->id}",
                );

                throw $e;
            }

            $account = SocialAccount::query()
                ->where('brand_id', $brand->id)
                ->where('social_platform_id', $platform->id)
                ->first();

            $hashtags = $this->buildPlatformHashtags($brand, $content, $platform);
            $cta = $this->buildPlatformCta($brand, $platform);
            $linkUrl = $this->buildTrackedUrl($brand, $content->source_url, $platform);

            $initialStatus = $submitForApproval
                ? SocialPost::STATUS_PENDING_APPROVAL
                : SocialPost::STATUS_DRAFT;

            $post = DB::transaction(function () use (
                $brand,
                $content,
                $account,
                $platform,
                $generation,
                $humanizedBody,
                $hashtags,
                $cta,
                $linkUrl,
                $initialStatus
            ) {
                $post = SocialPost::create([
                    'brand_id' => $brand->id,
                    'content_id' => $content->id,
                    'social_account_id' => $account?->id,
                    'social_platform_id' => $platform->id,
                    'ai_generation_id' => $generation->id,
                    'title' => $content->title,
                    'body' => $humanizedBody,
                    'hashtags' => $hashtags,
                    'cta' => $cta,
                    'link_url' => $linkUrl,
                    'status' => $initialStatus,
                    'approval_status' => SocialPost::APPROVAL_PENDING,
                    'publish_mode' => SocialPost::PUBLISH_MODE_SCHEDULED,
                    'idempotency_key' => 'sf_post_'.Str::uuid()->toString(),
                    'created_by' => Auth::id(),
                ]);

                foreach ($content->contentMedia as $mediaItem) {
                    SocialPostMedia::create([
                        'social_post_id' => $post->id,
                        'content_media_id' => $mediaItem->id,
                        'type' => $mediaItem->type,
                        'path' => $mediaItem->path,
                        'thumbnail' => $mediaItem->thumbnail,
                        'sort_order' => $mediaItem->sort_order,
                    ]);
                }

                return $post;
            });

            $createdPosts->push($post);
        }

        $content->update(['status' => Content::STATUS_GENERATED]);

        ActivityLog::record(
            action: 'ai.posts_generated',
            description: "Generated {$createdPosts->count()} platform-specific social post(s) for '{$content->title}'",
            subject: $content,
            brandId: $brand->id,
        );

        return $createdPosts;
    }

    /**
     * Re-run humanization on an existing SocialPost and log in ai_generations.
     */
    public function humanizeExistingPost(SocialPost $post): SocialPost
    {
        $post->loadMissing(['brand.brandVoiceSetting', 'content', 'socialPlatform']);

        $refined = $this->humanizationService->humanize(
            draft: $post->body,
            brand: $post->brand,
            sourceContent: $post->content,
            platform: $post->socialPlatform,
        );

        $template = $this->promptService->getActiveTemplate(PromptTemplate::PURPOSE_HUMANIZATION);

        $generation = AIGeneration::create([
            'brand_id' => $post->brand_id,
            'content_id' => $post->content_id,
            'social_platform_id' => $post->social_platform_id,
            'prompt_template_id' => $template?->id,
            'prompt_version' => $template?->version ?? 1,
            'generation_type' => AIGeneration::TYPE_HUMANIZATION,
            'input' => $post->body,
            'output' => $refined,
            'model' => 'sf-humanizer-v1',
            'input_tokens' => (int) (strlen($post->body) / 4),
            'output_tokens' => (int) (strlen($refined) / 4),
            'estimated_cost' => 0.0,
            'status' => AIGeneration::STATUS_COMPLETED,
        ]);

        $post->update([
            'body' => $refined,
            'ai_generation_id' => $generation->id,
        ]);

        ActivityLog::record(
            action: 'post.humanized',
            description: "Refined and humanized post #{$post->id} for {$post->brand->name}",
            subject: $post,
            brandId: $post->brand_id,
        );

        return $post->fresh();
    }

    protected function buildPlatformHashtags(Brand $brand, Content $content, SocialPlatform $platform): array
    {
        $defaults = $brand->brandSetting?->default_hashtags ?? ['#'.Str::studly($brand->name)];
        $optimalCount = (int) ($platform->configuration['optimal_hashtag_count'] ?? 3);

        $topicTag = '#'.Str::studly($content->type);
        $all = array_values(array_unique(array_merge($defaults, [$topicTag])));

        return array_slice($all, 0, max(1, $optimalCount));
    }

    protected function buildPlatformCta(Brand $brand, SocialPlatform $platform): ?string
    {
        $preferred = $brand->brandVoiceSetting?->preferred_phrases ?? [];

        if ($platform->slug === 'instagram') {
            return 'Link in bio.';
        }

        if ($platform->slug === 'x') {
            return null;
        }

        return $preferred[0] ?? null;
    }

    protected function buildTrackedUrl(Brand $brand, ?string $sourceUrl, SocialPlatform $platform): ?string
    {
        if (empty($sourceUrl)) {
            return $brand->website_url;
        }

        if ($platform->slug === 'instagram' && ($platform->configuration['link_in_bio_preferred'] ?? false)) {
            return null;
        }

        $tracking = $brand->brandSetting?->url_tracking_settings ?? [];
        if (empty($tracking)) {
            return $sourceUrl;
        }

        $separator = str_contains($sourceUrl, '?') ? '&' : '?';
        $params = array_filter([
            'utm_source' => $tracking['utm_source'] ?? 'socialforge',
            'utm_medium' => $platform->slug,
            'utm_campaign' => $tracking['utm_campaign'] ?? $brand->slug,
        ]);

        return $sourceUrl.$separator.http_build_query($params);
    }
}
