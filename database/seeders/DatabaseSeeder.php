<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@socialforge.local'],
            [
                'name' => 'SocialForge Super Admin',
                'password' => Hash::make('SocialForge!Admin2026'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'editor@socialforge.local'],
            [
                'name' => 'Editorial Manager',
                'password' => Hash::make('SocialForge!Editor2026'),
                'role' => User::ROLE_EDITOR,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'viewer@socialforge.local'],
            [
                'name' => 'Analytics Viewer',
                'password' => Hash::make('SocialForge!Viewer2026'),
                'role' => User::ROLE_VIEWER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            SocialPlatformSeeder::class,
            BrandSeeder::class,
            AIProviderAndPromptSeeder::class,
        ]);

        SystemSetting::setValue('platform_name', 'SocialForge', 'general', 'string');
        SystemSetting::setValue('default_require_approval', 'true', 'publishing', 'boolean');
        SystemSetting::setValue('token_refresh_threshold_hours', '48', 'security', 'integer');
        SystemSetting::setValue('max_publishing_retries', '3', 'publishing', 'integer');
    }
}
