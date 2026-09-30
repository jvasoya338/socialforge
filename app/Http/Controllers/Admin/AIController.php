<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AIGeneration;
use App\Models\AIProvider;
use App\Models\Brand;
use App\Models\BrandVoiceExample;
use App\Models\BrandVoiceSetting;
use App\Models\Content;
use App\Models\PromptTemplate;
use App\Models\SocialPlatform;
use App\Services\AI\AIContentService;
use App\Services\AI\AIProviderManager;
use App\Services\BrandContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AIController extends Controller
{
    public function __construct(
        protected BrandContext $brandContext,
        protected AIProviderManager $providerManager,
        protected AIContentService $aiContentService,
    ) {}

    public function generator(Request $request): View
    {
        $brands = Brand::orderBy('name')->get();
        $selectedBrand = $this->brandContext->current() ?: $brands->first();

        $contents = $selectedBrand
            ? Content::where('brand_id', $selectedBrand->id)->latest()->take(30)->get()
            : collect();

        $platforms = SocialPlatform::active()->get();
        $providers = AIProvider::active()->get();

        return view('admin.ai.generator', compact('brands', 'selectedBrand', 'contents', 'platforms', 'providers'));
    }

    public function runGeneration(Request $request): RedirectResponse
    {
        Gate::authorize('edit-content');

        $data = $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'content_id' => ['required', 'exists:contents,id'],
            'platform_ids' => ['required', 'array', 'min:1'],
            'platform_ids.*' => ['exists:social_platforms,id'],
            'ai_provider_id' => ['nullable', 'exists:ai_providers,id'],
            'submit_for_approval' => ['nullable', 'boolean'],
        ]);

        $brand = Brand::findOrFail($data['brand_id']);
        $content = Content::findOrFail($data['content_id']);
        $provider = ! empty($data['ai_provider_id']) ? AIProvider::find($data['ai_provider_id']) : null;

        try {
            $posts = $this->aiContentService->generatePostsForContent(
                brand: $brand,
                content: $content,
                platformIds: $data['platform_ids'],
                providerModel: $provider,
                submitForApproval: (bool) ($data['submit_for_approval'] ?? true),
            );

            return redirect()->route('admin.publishing.approval-queue')
                ->with('success', "Generated {$posts->count()} platform post variant(s) and submitted to approval queue.");
        } catch (\Throwable $e) {
            return back()->with('error', "AI Generation error: {$e->getMessage()}")->withInput();
        }
    }

    public function providers(): View
    {
        $providers = AIProvider::withCount('aiGenerations')->orderBy('priority')->get();

        return view('admin.ai.providers', compact('providers'));
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'unique:ai_providers,slug'],
            'driver' => ['required', 'in:openai,ollama,mock,custom'],
            'model' => ['required', 'string', 'max:120'],
            'base_url' => ['nullable', 'string', 'max:500'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if (! empty($data['is_default'])) {
            AIProvider::query()->update(['is_default' => false]);
        }

        $provider = AIProvider::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'driver' => $data['driver'],
            'model' => $data['model'],
            'base_url' => $data['base_url'] ?? null,
            'encrypted_api_key' => $data['api_key'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'status' => $data['status'],
        ]);

        ActivityLog::record(
            action: 'ai_provider.created',
            description: "Configured AI provider '{$provider->name}'",
            subject: $provider,
        );

        return back()->with('success', "AI Provider '{$provider->name}' created.");
    }

    public function testProvider(AIProvider $provider): RedirectResponse
    {
        Gate::authorize('manage-system');

        try {
            $instance = $this->providerManager->resolve($provider);
            $health = $instance->healthCheck();

            $provider->update([
                'last_tested_at' => now(),
                'last_health_status' => $health['healthy'] ? 'healthy' : 'unhealthy',
            ]);

            if ($health['healthy']) {
                return back()->with('success', "Provider '{$provider->name}' is healthy: {$health['message']}");
            }

            return back()->with('error', "Provider '{$provider->name}' health check failed: {$health['message']}");
        } catch (\Throwable $e) {
            $provider->update([
                'last_tested_at' => now(),
                'last_health_status' => 'error',
            ]);

            return back()->with('error', "Connection test failed: {$e->getMessage()}");
        }
    }

    public function brandVoice(Request $request): View
    {
        $brands = Brand::orderBy('name')->get();
        $brandId = $request->integer('brand_id') ?: $this->brandContext->id() ?: $brands->first()?->id;
        $brand = $brandId ? Brand::with(['brandVoiceSetting', 'brandVoiceExamples.socialPlatform'])->findOrFail($brandId) : null;
        $platforms = SocialPlatform::active()->get();

        return view('admin.ai.brand-voice', compact('brands', 'brand', 'platforms'));
    }

    public function updateBrandVoice(Request $request, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);

        $data = $request->validate([
            'tone' => ['required', 'string', 'max:255'],
            'writing_style' => ['required', 'string', 'max:500'],
            'audience' => ['nullable', 'string', 'max:500'],
            'emoji_style' => ['required', 'in:none,minimal,moderate,expressive'],
            'cta_style' => ['required', 'string', 'max:100'],
            'hashtag_style' => ['required', 'string', 'max:100'],
            'words_to_avoid' => ['nullable', 'string'],
            'phrases_to_avoid' => ['nullable', 'string'],
            'preferred_phrases' => ['nullable', 'string'],
            'additional_instructions' => ['nullable', 'string'],
            'preserve_strict_facts' => ['nullable', 'boolean'],
        ]);

        $splitArray = fn (?string $val) => $val ? array_values(array_filter(array_map('trim', explode(',', $val)))) : [];

        BrandVoiceSetting::query()->updateOrCreate(
            ['brand_id' => $brand->id],
            [
                'tone' => $data['tone'],
                'writing_style' => $data['writing_style'],
                'audience' => $data['audience'] ?? null,
                'emoji_style' => $data['emoji_style'],
                'cta_style' => $data['cta_style'],
                'hashtag_style' => $data['hashtag_style'],
                'words_to_avoid' => $splitArray($data['words_to_avoid'] ?? null),
                'phrases_to_avoid' => $splitArray($data['phrases_to_avoid'] ?? null),
                'preferred_phrases' => $splitArray($data['preferred_phrases'] ?? null),
                'additional_instructions' => $data['additional_instructions'] ?? null,
                'preserve_strict_facts' => (bool) ($data['preserve_strict_facts'] ?? false),
            ]
        );

        ActivityLog::record(
            action: 'brand_voice.updated',
            description: "Updated brand voice settings for {$brand->name}",
            subject: $brand,
            brandId: $brand->id,
        );

        return back()->with('success', "Brand voice settings updated for {$brand->name}.");
    }

    public function storeExample(Request $request, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);

        $data = $request->validate([
            'social_platform_id' => ['nullable', 'exists:social_platforms,id'],
            'content' => ['required', 'string', 'max:2000'],
            'approved' => ['nullable', 'boolean'],
        ]);

        BrandVoiceExample::create([
            'brand_id' => $brand->id,
            'social_platform_id' => $data['social_platform_id'] ?? null,
            'content' => $data['content'],
            'source' => 'admin_curated',
            'approved' => (bool) ($data['approved'] ?? true),
        ]);

        return back()->with('success', 'Added brand voice style example.');
    }

    public function toggleExampleApproved(BrandVoiceExample $example): RedirectResponse
    {
        Gate::authorize('update', $example->brand);

        $example->update(['approved' => ! $example->approved]);

        return back()->with('success', 'Toggled example approval status.');
    }

    public function deleteExample(BrandVoiceExample $example): RedirectResponse
    {
        Gate::authorize('update', $example->brand);

        $example->delete();

        return back()->with('success', 'Deleted brand voice example.');
    }

    public function prompts(): View
    {
        $prompts = PromptTemplate::orderBy('purpose')->get();

        return view('admin.ai.prompts', compact('prompts'));
    }

    public function updatePrompt(Request $request, PromptTemplate $prompt): RedirectResponse
    {
        Gate::authorize('manage-system');

        $data = $request->validate([
            'system_prompt' => ['required', 'string'],
            'user_prompt_template' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'bump_version' => ['nullable', 'boolean'],
        ]);

        $newVersion = ! empty($data['bump_version']) ? $prompt->version + 1 : $prompt->version;

        $prompt->update([
            'system_prompt' => $data['system_prompt'],
            'user_prompt_template' => $data['user_prompt_template'],
            'status' => $data['status'],
            'version' => $newVersion,
        ]);

        ActivityLog::record(
            action: 'prompt.updated',
            description: "Updated prompt template '{$prompt->name}' (version {$newVersion})",
            subject: $prompt,
        );

        return back()->with('success', "Updated prompt template '{$prompt->name}' (version {$newVersion}).");
    }

    public function history(Request $request): View
    {
        $query = $this->brandContext->apply(
            AIGeneration::with(['brand', 'content', 'socialPlatform', 'aiProvider', 'promptTemplate']),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($type = $request->input('generation_type')) {
            $query->where('generation_type', $type);
        }

        $generations = $query->latest()->paginate(20)->withQueryString();

        return view('admin.ai.history', compact('generations'));
    }
}
