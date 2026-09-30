<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@socialforge.io',
            'password' => Hash::make('SecretPass123!'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@socialforge.io',
            'password' => 'SecretPass123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@socialforge.io',
            'password' => Hash::make('SecretPass123!'),
            'role' => User::ROLE_EDITOR,
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@socialforge.io',
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_access_system_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/system/settings');
        $response->assertStatus(200);
    }

    public function test_editor_cannot_access_system_settings_or_users(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($editor)->get('/admin/system/settings');
        $response->assertStatus(403);

        $usersResponse = $this->actingAs($editor)->get('/admin/system/users');
        $usersResponse->assertStatus(403);
    }

    public function test_viewer_has_read_only_access_and_cannot_create_brands(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        // Viewer can view brands
        $viewResponse = $this->actingAs($viewer)->get('/admin/brands');
        $viewResponse->assertStatus(200);

        // Viewer cannot store new brand
        $createResponse = $this->actingAs($viewer)->post('/admin/brands', [
            'name' => 'Unauthorized Brand',
            'slug' => 'unauthorized-brand',
            'timezone' => 'UTC',
        ]);
        $createResponse->assertStatus(403);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
