<?php

namespace Tests\Feature;

use App\Jobs\PublishSocialPostJob;
use App\Models\Brand;
use App\Models\BrandSetting;
use App\Models\PublishingAttempt;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Models\SocialPlatform;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Publishing\SocialPublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublishingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $editorUser;

    protected Brand $brand;

    protected SocialPlatform $platform;

    protected SocialAccount $account;

    protected SocialCredential $credential;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->editorUser = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create(['name' => 'Publishing Brand']);
        BrandSetting::create([
            'brand_id' => $this->brand->id,
            'approval_required' => true,
        ]);

        $this->platform = SocialPlatform::factory()->create([
            'name' => 'X',
            'slug' => 'x',
            'max_text_length' => 280,
        ]);

        $this->account = SocialAccount::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'account_name' => 'Publish Brand X',
            'username' => 'pub_brand_x',
            'connection_status' => SocialAccount::CONNECTION_CONNECTED,
        ]);

        $this->credential = SocialCredential::create([
            'social_account_id' => $this->account->id,
            'access_token' => 'valid_bearer_token_x_123',
            'refresh_token' => 'valid_refresh_token_x_456',
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_post_approval_workflow_transitions(): void
    {
        $post = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'social_account_id' => $this->account->id,
            'created_by' => $this->editorUser->id,
            'body' => 'Exciting news coming soon to our platform!',
            'status' => SocialPost::STATUS_PENDING_APPROVAL,
            'approval_status' => SocialPost::APPROVAL_PENDING,
        ]);

        // Admin approves post
        $approveResponse = $this->actingAs($this->adminUser)
            ->post("/admin/publishing/posts/{$post->id}/approve");

        $approveResponse->assertRedirect();
        $this->assertEquals(SocialPost::STATUS_APPROVED, $post->fresh()->status);
        $this->assertEquals(SocialPost::APPROVAL_APPROVED, $post->fresh()->approval_status);
        $this->assertEquals($this->adminUser->id, $post->fresh()->approved_by);

        // Admin rejects post back to draft
        $rejectResponse = $this->actingAs($this->adminUser)
            ->post("/admin/publishing/posts/{$post->id}/reject", [
                'rejection_reason' => 'Needs more specific timing details.',
            ]);

        $rejectResponse->assertRedirect();
        $this->assertEquals(SocialPost::STATUS_DRAFT, $post->fresh()->status);
        $this->assertEquals(SocialPost::APPROVAL_REJECTED, $post->fresh()->approval_status);
        $this->assertEquals('Needs more specific timing details.', $post->fresh()->rejection_reason);
    }

    public function test_editor_cannot_approve_posts(): void
    {
        $post = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'status' => SocialPost::STATUS_PENDING_APPROVAL,
            'approval_status' => SocialPost::APPROVAL_PENDING,
        ]);

        $response = $this->actingAs($this->editorUser)
            ->post("/admin/publishing/posts/{$post->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals(SocialPost::STATUS_PENDING_APPROVAL, $post->fresh()->status);
    }

    public function test_publish_social_post_job_success(): void
    {
        Http::fake([
            'https://api.x.com/*' => Http::response([
                'data' => [
                    'id' => 'tweet_987654321',
                    'text' => 'Live on X: New product features released!',
                ],
            ], 201),
        ]);

        $post = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'social_account_id' => $this->account->id,
            'body' => 'Live on X: New product features released!',
            'status' => SocialPost::STATUS_APPROVED,
            'approval_status' => SocialPost::APPROVAL_APPROVED,
        ]);

        $job = new PublishSocialPostJob($post->id, ignoreScheduleTime: true);
        $job->handle(app(SocialPublishingService::class));

        $freshPost = $post->fresh();
        $this->assertEquals(SocialPost::STATUS_PUBLISHED, $freshPost->status);
        $this->assertEquals('tweet_987654321', $freshPost->platform_post_id);
        $this->assertNotNull($freshPost->published_at);

        $attempt = PublishingAttempt::where('social_post_id', $post->id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals(PublishingAttempt::STATUS_SUCCESS, $attempt->status);
        $this->assertEquals('tweet_987654321', $attempt->platform_post_id);
    }

    public function test_publishing_with_unauthorized_token_marks_account_reconnect_required(): void
    {
        Http::fake([
            'https://api.x.com/*' => Http::response([
                'error' => [
                    'message' => 'The access token is invalid, expired, or revoked.',
                    'code' => 89,
                ],
            ], 401),
        ]);

        $post = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'social_account_id' => $this->account->id,
            'body' => 'Post destined to fail due to expired credentials.',
            'status' => SocialPost::STATUS_APPROVED,
            'approval_status' => SocialPost::APPROVAL_APPROVED,
        ]);

        $job = new PublishSocialPostJob($post->id, ignoreScheduleTime: true);
        $job->handle(app(SocialPublishingService::class));

        $freshPost = $post->fresh();
        $this->assertEquals(SocialPost::STATUS_FAILED, $freshPost->status);
        $this->assertEquals(PublishingAttempt::CATEGORY_AUTHENTICATION, $freshPost->failure_category);

        // Account must be flagged as reconnect_required
        $this->assertEquals(
            SocialAccount::CONNECTION_RECONNECT_REQUIRED,
            $this->account->fresh()->connection_status
        );

        $attempt = PublishingAttempt::where('social_post_id', $post->id)->first();
        $this->assertEquals(PublishingAttempt::STATUS_FAILED, $attempt->status);
        $this->assertEquals(PublishingAttempt::CATEGORY_AUTHENTICATION, $attempt->failure_category);
    }

    public function test_publishing_idempotency_prevents_duplicate_publication(): void
    {
        Http::fake();

        $alreadyPublishedPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'social_account_id' => $this->account->id,
            'body' => 'Already published content',
            'status' => SocialPost::STATUS_PUBLISHED,
            'platform_post_id' => 'tweet_already_published_111',
            'published_at' => now()->subDay(),
        ]);

        $job = new PublishSocialPostJob($alreadyPublishedPost->id, ignoreScheduleTime: true);
        $job->handle(app(SocialPublishingService::class));

        // Assert that no HTTP requests were triggered
        Http::assertNothingSent();
    }
}
