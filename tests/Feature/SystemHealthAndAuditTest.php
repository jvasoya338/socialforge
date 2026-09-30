<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $editorUser;

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
    }

    public function test_activity_log_automatically_scrubs_secrets_from_properties(): void
    {
        $brand = Brand::factory()->create(['name' => 'Security Audit Brand']);

        $sensitivePayload = [
            'username' => 'auditor_user',
            'action_note' => 'Connected external account',
            'access_token' => 'live_token_12345_do_not_leak',
            'refresh_token' => 'live_refresh_98765_do_not_leak',
            'client_secret' => 'app_client_secret_xyz',
            'api_key' => 'sf_live_abc1234567890',
            'nested_config' => [
                'webhook_secret' => 'whsec_private_signing_key',
                'public_id' => 'slot_101',
            ],
        ];

        $log = ActivityLog::record(
            action: 'test.security_audit',
            description: 'Security audit test record',
            subject: $brand,
            properties: $sensitivePayload,
            userId: $this->adminUser->id
        );

        $savedLog = ActivityLog::find($log->id);
        $props = $savedLog->properties;

        // Non-sensitive fields preserved
        $this->assertEquals('auditor_user', $props['username']);
        $this->assertEquals('Connected external account', $props['action_note']);
        $this->assertEquals('slot_101', $props['nested_config']['public_id']);

        // Sensitive fields permanently sanitized
        $this->assertEquals('[REDACTED]', $props['access_token']);
        $this->assertEquals('[REDACTED]', $props['refresh_token']);
        $this->assertEquals('[REDACTED]', $props['client_secret']);
        $this->assertEquals('[REDACTED]', $props['api_key']);
        $this->assertEquals('[REDACTED]', $props['nested_config']['webhook_secret']);

        // Assert plaintext secrets are not found anywhere in database JSON column
        $rawJson = json_encode($props);
        $this->assertStringNotContainsString('live_token_12345_do_not_leak', $rawJson);
        $this->assertStringNotContainsString('live_refresh_98765_do_not_leak', $rawJson);
        $this->assertStringNotContainsString('whsec_private_signing_key', $rawJson);
    }

    public function test_system_health_service_reports_valid_statuses(): void
    {
        $healthService = app(SystemHealthService::class);
        $report = $healthService->checkAll();

        $this->assertArrayHasKey('application', $report);
        $this->assertArrayHasKey('database', $report);
        $this->assertArrayHasKey('queue', $report);
        $this->assertArrayHasKey('storage', $report);

        $this->assertTrue($report['application']['healthy']);
        $this->assertTrue($report['database']['healthy']);
    }

    public function test_activity_logs_page_access_permissions(): void
    {
        // Super Admin has access
        $adminResponse = $this->actingAs($this->adminUser)->get('/admin/system/activity-logs');
        $adminResponse->assertStatus(200);

        // Editor does not have access
        $editorResponse = $this->actingAs($this->editorUser)->get('/admin/system/activity-logs');
        $editorResponse->assertStatus(403);
    }
}
