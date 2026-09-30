<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Models\SocialPlatform;
use App\Models\User;
use App\Services\SocialAuth\CredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SocialCredentialTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Brand $brand;

    protected SocialPlatform $platform;

    protected SocialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create(['name' => 'Credential Brand']);
        $this->platform = SocialPlatform::factory()->create(['slug' => 'x']);
        $this->account = SocialAccount::factory()->create([
            'brand_id' => $this->brand->id,
            'social_platform_id' => $this->platform->id,
        ]);
    }

    public function test_credentials_are_encrypted_at_rest_in_database(): void
    {
        $rawAccessToken = 'sec_token_live_abc123xyz_super_secret';
        $rawRefreshToken = 'sec_refresh_live_def456_super_secret';

        $credential = SocialCredential::create([
            'social_account_id' => $this->account->id,
            'access_token' => $rawAccessToken,
            'refresh_token' => $rawRefreshToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30),
            'scopes' => ['tweet.read', 'tweet.write', 'users.read'],
            'metadata' => ['user_id' => '123456789'],
        ]);

        // Direct database query bypassing Eloquent casts
        $rawRow = DB::table('social_credentials')->where('id', $credential->id)->first();

        // Database raw values must NOT match the raw plaintext tokens
        $this->assertNotEquals($rawAccessToken, $rawRow->access_token);
        $this->assertNotEquals($rawRefreshToken, $rawRow->refresh_token);

        // When retrieved through Crypt::decryptString, it resolves to original
        $this->assertEquals($rawAccessToken, Crypt::decryptString($rawRow->access_token));
        $this->assertEquals($rawRefreshToken, Crypt::decryptString($rawRow->refresh_token));

        // When accessed through Eloquent model, decrypted automatically
        $this->assertEquals($rawAccessToken, $credential->fresh()->access_token);
    }

    public function test_credentials_are_hidden_from_array_and_json_serialization(): void
    {
        $credential = SocialCredential::create([
            'social_account_id' => $this->account->id,
            'access_token' => 'super_secret_token_123',
            'refresh_token' => 'super_secret_refresh_456',
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30),
        ]);

        $array = $credential->toArray();
        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertArrayNotHasKey('refresh_token', $array);

        $json = $credential->toJson();
        $this->assertStringNotContainsString('super_secret_token_123', $json);
        $this->assertStringNotContainsString('super_secret_refresh_456', $json);
    }

    public function test_token_expiration_states_are_accurately_evaluated(): void
    {
        $account1 = SocialAccount::factory()->create(['brand_id' => $this->brand->id, 'social_platform_id' => $this->platform->id]);
        $account2 = SocialAccount::factory()->create(['brand_id' => $this->brand->id, 'social_platform_id' => $this->platform->id]);
        $account3 = SocialAccount::factory()->create(['brand_id' => $this->brand->id, 'social_platform_id' => $this->platform->id]);

        // Expired credential
        $expiredCredential = SocialCredential::create([
            'social_account_id' => $account1->id,
            'access_token' => 'token_expired',
            'expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($expiredCredential->isExpired());
        $this->assertFalse($expiredCredential->isExpiringSoon());
        $this->assertFalse($expiredCredential->hasValidAccessToken());

        // Expiring soon (e.g. 12 hours from now)
        $expiringCredential = SocialCredential::create([
            'social_account_id' => $account2->id,
            'access_token' => 'token_expiring',
            'expires_at' => now()->addHours(12),
        ]);

        $this->assertFalse($expiringCredential->isExpired());
        $this->assertTrue($expiringCredential->isExpiringSoon(48));
        $this->assertTrue($expiringCredential->hasValidAccessToken());

        // Long-lived active credential (60 days out)
        $activeCredential = SocialCredential::create([
            'social_account_id' => $account3->id,
            'access_token' => 'token_active',
            'expires_at' => now()->addDays(60),
        ]);

        $this->assertFalse($activeCredential->isExpired());
        $this->assertFalse($activeCredential->isExpiringSoon(48));
        $this->assertTrue($activeCredential->hasValidAccessToken());
    }

    public function test_credential_service_can_revoke_and_disconnect_account(): void
    {
        SocialCredential::create([
            'social_account_id' => $this->account->id,
            'access_token' => 'token_to_disconnect',
            'expires_at' => now()->addDays(30),
        ]);

        $credentialService = app(CredentialService::class);
        $credentialService->disconnect($this->account);

        $freshAccount = $this->account->fresh(['credential']);
        $this->assertEquals(SocialAccount::CONNECTION_DISCONNECTED, $freshAccount->connection_status);
        $this->assertNull($freshAccount->credential->access_token);
        $this->assertNull($freshAccount->credential->refresh_token);
    }
}
