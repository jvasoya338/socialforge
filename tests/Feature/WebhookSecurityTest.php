<?php

namespace Tests\Feature;

use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'test_webhook_signing_secret_999';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.webhooks.signing_secret', $this->secret);
    }

    public function test_webhook_with_valid_hmac_signature_is_accepted(): void
    {
        $payload = [
            'event_id' => 'evt_valid_1001',
            'event_type' => 'social_post.published',
            'data' => ['metric' => 'engaged'],
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, $this->secret);

        $response = $this->withHeaders([
            'X-SocialForge-Signature' => "sha256={$signature}",
        ])->postJson('/api/webhooks/generic', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'duplicate' => false,
            ]);

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'generic',
            'external_event_id' => 'evt_valid_1001',
            'signature_valid' => 1,
        ]);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $payload = [
            'event_id' => 'evt_tampered_2002',
            'event_type' => 'social_post.deleted',
        ];

        $response = $this->withHeaders([
            'X-SocialForge-Signature' => 'sha256=invalid_tampered_signature_string',
        ])->postJson('/api/webhooks/generic', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'rejected',
                'message' => 'Invalid webhook signature.',
            ]);

        $this->assertDatabaseHas('webhook_events', [
            'external_event_id' => 'evt_tampered_2002',
            'signature_valid' => 0,
            'status' => WebhookEvent::STATUS_FAILED,
        ]);
    }

    public function test_duplicate_webhook_event_id_is_detected_and_handled_idempotently(): void
    {
        $payload = [
            'event_id' => 'evt_duplicate_replay_3003',
            'event_type' => 'platform.ping',
        ];

        $rawBody = json_encode($payload);
        $signature = hash_hmac('sha256', $rawBody, $this->secret);

        // First send
        $firstResponse = $this->withHeaders([
            'X-SocialForge-Signature' => "sha256={$signature}",
        ])->postJson('/api/webhooks/generic', $payload);

        $firstResponse->assertStatus(200)
            ->assertJson(['duplicate' => false]);

        // Second replay send
        $secondResponse = $this->withHeaders([
            'X-SocialForge-Signature' => "sha256={$signature}",
        ])->postJson('/api/webhooks/generic', $payload);

        $secondResponse->assertStatus(200)
            ->assertJson(['duplicate' => true]);

        // Assert exactly one record was persisted in database
        $this->assertEquals(
            1,
            WebhookEvent::where('external_event_id', 'evt_duplicate_replay_3003')->count()
        );
    }
}
