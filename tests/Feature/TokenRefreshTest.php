<?php

namespace Tests\Feature;

use App\Jobs\RefreshSocialTokenJob;
use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Models\SocialPlatform;
use App\Services\SocialAuth\TokenRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $brand;

    protected SocialPlatform $platform;

    protected SocialAccount $account;

    protected SocialCredential $credential;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.social_platforms.x.client_id', 'test_client_id_x');
        Config::set('services.social_platforms.x.client_secret', 'test_client_secret_x');

        $this->brand = Brand::factory()->create(['name' => 'Token Test Brand']);
        $this->platform = SocialPlatform::factory()->create([
            'name' => 'X',
            'slug' => 'x',
        ]);

        $this->account = SocialAccount::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'connection_status' => SocialAccount::CONNECTION_EXPIRING_SOON,
        ]);

        $this->credential = SocialCredential::create([
            'social_account_id' => $this->account->id,
            'access_token' => 'old_access_token_111',
            'refresh_token' => 'active_refresh_token_222',
            'expires_at' => now()->addHours(6),
        ]);
    }

    public function test_token_refresh_job_successfully_refreshes_token(): void
    {
        Http::fake([
            'https://api.x.com/2/oauth2/token' => Http::response([
                'access_token' => 'new_fresh_token_999',
                'refresh_token' => 'new_fresh_refresh_888',
                'expires_in' => 86400 * 30,
                'token_type' => 'Bearer',
                'scope' => 'tweet.read tweet.write',
            ], 200),
        ]);

        $job = new RefreshSocialTokenJob($this->account->id);
        $job->handle(app(TokenRefreshService::class));

        $freshAccount = $this->account->fresh(['credential']);
        $this->assertEquals(SocialAccount::CONNECTION_CONNECTED, $freshAccount->connection_status);

        $freshCred = $freshAccount->credential;
        $this->assertEquals('new_fresh_token_999', $freshCred->access_token);
        $this->assertEquals('new_fresh_refresh_888', $freshCred->refresh_token);
        $this->assertNotNull($freshCred->last_refreshed_at);
        $this->assertTrue($freshCred->expires_at->isFuture());
    }

    public function test_token_refresh_job_handles_revocation_and_flags_reconnect_required(): void
    {
        Http::fake([
            'https://api.x.com/2/oauth2/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'The provided refresh token is expired or revoked.',
            ], 400),
        ]);

        $job = new RefreshSocialTokenJob($this->account->id);
        $job->handle(app(TokenRefreshService::class));

        $freshAccount = $this->account->fresh(['credential']);
        $this->assertEquals(SocialAccount::CONNECTION_RECONNECT_REQUIRED, $freshAccount->connection_status);

        $freshCred = $freshAccount->credential;
        $this->assertNotNull($freshCred->last_refresh_error);
        $this->assertStringContainsString('The provided refresh token is expired or revoked', $freshCred->last_refresh_error);
    }

    public function test_token_refresh_service_identifies_expiring_credentials(): void
    {
        $accountLongLived = SocialAccount::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
            'connection_status' => SocialAccount::CONNECTION_CONNECTED,
        ]);

        SocialCredential::create([
            'social_account_id' => $accountLongLived->id,
            'access_token' => 'long_lived_token',
            'refresh_token' => 'long_lived_refresh',
            'expires_at' => now()->addDays(30),
        ]);

        $service = app(TokenRefreshService::class);
        $expiring = $service->getExpiringCredentials(48);

        // $this->credential expires in 6 hours, so it must be included
        $this->assertTrue($expiring->contains('id', $this->credential->id));

        // $accountLongLived credential expires in 30 days, so it must NOT be included
        $this->assertFalse($expiring->contains('social_account_id', $accountLongLived->id));
    }
}
