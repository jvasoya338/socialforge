<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use App\Models\User;
use App\Services\BrandContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandManagementTest extends TestCase
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

    public function test_admin_can_create_brand_with_settings_and_voice(): void
    {
        $payload = [
            'name' => 'Aetheria Luxe',
            'slug' => 'aetheria-luxe',
            'description' => 'Fine artisanal perfumes and scents',
            'website_url' => 'https://aetheria.example.com',
            'timezone' => 'America/New_York',
            'default_language' => 'en',
            'status' => 'active',
            'primary_color' => '#1a365d',
            'secondary_color' => '#d69e2e',
            'voice_summary' => 'Sophisticated, poetic, evocative, whispering opulence.',
            'tone' => 'luxurious',
            'formality_level' => 'elevated',
            'target_audience' => 'Discerning fragrance connoisseurs',
            'avoid_words' => 'cheap, discount, bargain, quick',
            'preferred_words' => 'artisanal, bespoke, olfactory, exquisite',
        ];

        $response = $this->actingAs($this->adminUser)->post('/admin/brands', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('brands', [
            'name' => 'Aetheria Luxe',
            'slug' => 'aetheria-luxe',
            'timezone' => 'America/New_York',
            'status' => Brand::STATUS_ACTIVE,
        ]);

        $brand = Brand::where('slug', 'aetheria-luxe')->first();
        $this->assertNotNull($brand->brandSetting);
        $this->assertEquals('America/New_York', $brand->brandSetting->default_timezone);

        $this->assertNotNull($brand->brandVoiceSetting);
        $this->assertEquals('luxurious', $brand->brandVoiceSetting->tone);
    }

    public function test_admin_can_update_brand(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Original Brand',
            'slug' => 'original-brand',
        ]);

        $response = $this->actingAs($this->adminUser)->put("/admin/brands/{$brand->id}", [
            'name' => 'Updated Brand Name',
            'slug' => 'original-brand',
            'timezone' => 'UTC',
            'default_language' => 'en',
            'status' => Brand::STATUS_ACTIVE,
            'tone' => 'friendly',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand Name',
        ]);
    }

    public function test_admin_can_toggle_brand_status(): void
    {
        $brand = Brand::factory()->create(['status' => Brand::STATUS_ACTIVE]);

        $response = $this->actingAs($this->adminUser)->post("/admin/brands/{$brand->id}/toggle");
        $response->assertRedirect();

        $this->assertEquals(Brand::STATUS_INACTIVE, $brand->fresh()->status);

        $this->actingAs($this->adminUser)->post("/admin/brands/{$brand->id}/toggle");
        $this->assertEquals(Brand::STATUS_ACTIVE, $brand->fresh()->status);
    }

    public function test_user_can_switch_brand_context(): void
    {
        $brandA = Brand::factory()->create(['name' => 'Brand Alpha']);
        $brandB = Brand::factory()->create(['name' => 'Brand Beta']);

        $response = $this->actingAs($this->adminUser)->post('/admin/switch-brand', [
            'brand_id' => $brandA->id,
        ]);

        $response->assertSessionHas('selected_brand_id', $brandA->id);
        $this->assertEquals($brandA->id, $this->adminUser->fresh()->current_brand_id);

        // Switch to all brands
        $resetResponse = $this->actingAs($this->adminUser)->post('/admin/switch-brand', [
            'brand_id' => 'all',
        ]);

        $resetResponse->assertSessionMissing('selected_brand_id');
        $this->assertNull($this->adminUser->fresh()->current_brand_id);
    }

    public function test_brand_context_isolates_resources(): void
    {
        $brandA = Brand::factory()->create(['name' => 'Brand Alpha']);
        $brandB = Brand::factory()->create(['name' => 'Brand Beta']);

        $platform = SocialPlatform::factory()->create([
            'name' => 'X',
            'slug' => 'x',
            'max_text_length' => 280,
        ]);

        $accountA = SocialAccount::create([
            'brand_id' => $brandA->id,
            'social_platform_id' => $platform->id,
            'account_name' => 'Alpha Official',
            'account_identifier' => 'alpha_x',
            'connection_status' => SocialAccount::CONNECTION_CONNECTED,
        ]);

        $accountB = SocialAccount::create([
            'brand_id' => $brandB->id,
            'social_platform_id' => $platform->id,
            'account_name' => 'Beta Official',
            'account_identifier' => 'beta_x',
            'connection_status' => SocialAccount::CONNECTION_CONNECTED,
        ]);

        $brandContext = app(BrandContext::class);
        $brandContext->setBrand($brandA);

        $filteredAccounts = $brandContext->apply(SocialAccount::query())->get();

        $this->assertTrue($filteredAccounts->contains('id', $accountA->id));
        $this->assertFalse($filteredAccounts->contains('id', $accountB->id));
    }
}
