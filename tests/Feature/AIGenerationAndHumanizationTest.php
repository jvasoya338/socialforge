<?php

namespace Tests\Feature;

use App\Models\AIGeneration;
use App\Models\AIProvider;
use App\Models\Brand;
use App\Models\BrandSetting;
use App\Models\BrandVoiceSetting;
use App\Models\Content;
use App\Models\PromptTemplate;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\AI\AIContentService;
use App\Services\AI\HumanizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIGenerationAndHumanizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_humanization_service_removes_generic_ai_cliches(): void
    {
        $brand = Brand::factory()->create(['name' => 'TechPulse']);
        BrandVoiceSetting::create([
            'brand_id' => $brand->id,
            'tone' => 'direct, modern',
            'writing_style' => 'concise and engaging',
        ]);

        $service = app(HumanizationService::class);

        $rawAIText = "In today's fast-paced digital world, let's delve into this game-changer! It is a testament to innovation and a rich tapestry of modern tech.";
        $humanized = $service->humanize($rawAIText, $brand);

        $this->assertStringNotContainsString("in today's fast-paced digital world", strtolower($humanized));
        $this->assertStringNotContainsString('delve into', strtolower($humanized));
        $this->assertStringNotContainsString('game-changer', strtolower($humanized));
        $this->assertStringNotContainsString('a testament to', strtolower($humanized));
        $this->assertStringNotContainsString('rich tapestry of', strtolower($humanized));
    }

    public function test_humanization_service_enforces_brand_avoid_words(): void
    {
        $brand = Brand::factory()->create(['name' => 'GildedVow']);
        BrandVoiceSetting::create([
            'brand_id' => $brand->id,
            'tone' => 'luxurious, romantic',
            'writing_style' => 'poetic and elegant',
            'words_to_avoid' => ['cheap', 'discount', 'bargain'],
            'phrases_to_avoid' => ['lowest price in town', 'buy now or miss out'],
        ]);

        $service = app(HumanizationService::class);

        $rawText = 'Find cheap jewelry with a special discount! Lowest price in town for handcrafted gold.';
        $humanized = $service->humanize($rawText, $brand);

        $this->assertStringNotContainsString('cheap', strtolower($humanized));
        $this->assertStringNotContainsString('discount', strtolower($humanized));
        $this->assertStringNotContainsString('lowest price in town', strtolower($humanized));
    }

    public function test_humanization_service_strictly_preserves_news_facts_and_neutralizes_sensationalism(): void
    {
        $brand = Brand::factory()->create(['name' => 'NewsCrestle']);
        BrandSetting::create([
            'brand_id' => $brand->id,
            'require_strict_factual_check' => true,
        ]);
        BrandVoiceSetting::create([
            'brand_id' => $brand->id,
            'tone' => 'objective, factual, analytical',
            'writing_style' => 'straightforward journalese',
            'preserve_strict_facts' => true,
        ]);

        $service = app(HumanizationService::class);

        // Sensationalist text with multiple exclamations
        $rawSensationalNews = 'SHOCKING discovery in biomedical research!!! Scientists undeniably confirmed a miracle cure!';
        $humanized = $service->humanize($rawSensationalNews, $brand);

        // Sensational words must be purged
        $this->assertStringNotContainsString('shocking', strtolower($humanized));
        $this->assertStringNotContainsString('miracle', strtolower($humanized));
        $this->assertStringNotContainsString('undeniably confirmed', strtolower($humanized));
        // Exclamations replaced with neutral periods
        $this->assertStringNotContainsString('!', $humanized);
    }

    public function test_ai_content_service_generates_posts_and_records_audit_trail(): void
    {
        $brand = Brand::factory()->create(['name' => 'Gamozy']);
        BrandSetting::create([
            'brand_id' => $brand->id,
            'approval_required' => true,
            'default_hashtags' => ['#Gaming', '#FreeToPlay'],
        ]);
        BrandVoiceSetting::create([
            'brand_id' => $brand->id,
            'tone' => 'energetic, engaging',
            'writing_style' => 'playful and gamer-oriented',
        ]);

        $platform = SocialPlatform::factory()->create([
            'name' => 'X',
            'slug' => 'x',
            'max_text_length' => 280,
        ]);

        SocialAccount::factory()->create([
            'brand_id' => $brand->id,
            'social_platform_id' => $platform->id,
            'account_name' => 'Gamozy Official',
        ]);

        $provider = AIProvider::create([
            'name' => 'Structured Mock Engine',
            'slug' => 'structured-mock',
            'driver' => 'structured',
            'model' => 'deterministic-brand-v1',
            'is_active' => true,
            'is_default' => true,
        ]);

        $promptTemplate = PromptTemplate::create([
            'name' => 'Social Post Generation',
            'slug' => 'social-post-generation',
            'purpose' => PromptTemplate::PURPOSE_CONTENT_GENERATION,
            'system_prompt' => 'You are an expert social media manager.',
            'user_prompt_template' => 'Generate an engaging post for {content_title}. Summary: {content_summary}',
            'version' => 1,
            'status' => PromptTemplate::STATUS_ACTIVE,
        ]);

        $content = Content::create([
            'brand_id' => $brand->id,
            'external_id' => 'nebula-striker-2026',
            'type' => Content::TYPE_GAME,
            'title' => 'Nebula Striker Championship 2026',
            'summary' => 'High octane arcade spaceship combat tournament starts this Friday.',
            'body' => 'Full tournament rules, prize pools, and leaderboard qualifiers.',
            'status' => Content::STATUS_READY,
        ]);

        $aiService = app(AIContentService::class);
        $posts = $aiService->generatePostsForContent(
            brand: $brand,
            content: $content,
            platformIds: [$platform->id],
            providerModel: $provider,
            submitForApproval: true
        );

        $this->assertCount(1, $posts);
        $post = $posts->first();

        $this->assertEquals($brand->id, $post->brand_id);
        $this->assertEquals($platform->id, $post->social_platform_id);
        $this->assertEquals(SocialPost::STATUS_PENDING_APPROVAL, $post->status);
        $this->assertNotEmpty($post->body);
        $this->assertContains('#Gaming', $post->hashtags ?? []);

        // Assert AI generation log record was created
        $this->assertDatabaseHas('ai_generations', [
            'brand_id' => $brand->id,
            'content_id' => $content->id,
            'social_platform_id' => $platform->id,
            'status' => AIGeneration::STATUS_COMPLETED,
        ]);
    }
}
