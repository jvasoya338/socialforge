<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\WebhookEvent;
use App\Notifications\OperationalAlertNotification;
use App\Services\Content\ContentImportService;
use Illuminate\Http\Request;

class WebhookService
{
    public function __construct(protected ContentImportService $contentImportService) {}

    public function verifySignature(string $provider, string $rawPayload, ?string $signatureHeader): bool
    {
        $secret = match (strtolower($provider)) {
            'facebook', 'instagram' => config('services.webhooks.facebook_secret') ?: config('services.webhooks.signing_secret'),
            'x' => config('services.webhooks.x_secret') ?: config('services.webhooks.signing_secret'),
            default => config('services.webhooks.signing_secret'),
        };

        if (empty($secret) || empty($signatureHeader)) {
            return false;
        }

        $cleanSig = str_replace('sha256=', '', trim($signatureHeader));
        $expected = hash_hmac('sha256', $rawPayload, (string) $secret);

        return hash_equals($expected, $cleanSig);
    }

    /**
     * Handle incoming webhook with signature verification and duplicate event protection.
     *
     * @return array{event: WebhookEvent, duplicate: bool}
     */
    public function handleIncomingWebhook(string $provider, Request $request): array
    {
        $rawBody = $request->getContent();
        $payload = $request->json()->all() ?: $request->all();
        $sanitizedPayload = ActivityLog::sanitizeProperties(is_array($payload) ? $payload : []);

        $signatureHeader = $request->header('X-SocialForge-Signature')
            ?: $request->header('X-Hub-Signature-256')
            ?: $request->header('X-Webhook-Signature');

        $signatureValid = $this->verifySignature($provider, $rawBody, $signatureHeader);

        $externalEventId = (string) ($payload['event_id'] ?? $payload['id'] ?? '');
        $eventType = (string) ($payload['event_type'] ?? $payload['type'] ?? 'webhook.received');

        if ($externalEventId !== '') {
            $existing = WebhookEvent::query()
                ->where('provider', strtolower($provider))
                ->where('external_event_id', $externalEventId)
                ->first();

            if ($existing) {
                return [
                    'event' => $existing,
                    'duplicate' => true,
                ];
            }
        }

        $event = WebhookEvent::create([
            'provider' => strtolower($provider),
            'event_type' => $eventType,
            'external_event_id' => $externalEventId !== '' ? $externalEventId : null,
            'signature_valid' => $signatureValid,
            'payload' => $sanitizedPayload,
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        if (! $signatureValid) {
            $event->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'error_message' => 'Invalid or missing webhook HMAC signature.',
            ]);

            OperationalAlertNotification::notifyAdmins(
                alertType: 'webhook_failed',
                title: "Webhook Signature Verification Failed ({$provider})",
                message: "Rejected unverified webhook event '{$eventType}' from {$provider}.",
                severity: 'warning',
                actionUrl: route('admin.system.webhooks'),
                deduplicationKey: "webhook_sig_failed:{$provider}",
            );

            return [
                'event' => $event,
                'duplicate' => false,
            ];
        }

        try {
            if (isset($payload['content']) && is_array($payload['content']) && ! empty($payload['brand_slug'])) {
                $brand = Brand::where('slug', $payload['brand_slug'])->first();
                if ($brand && ! empty($payload['content']['title'])) {
                    $this->contentImportService->importContent($brand, $payload['content']);
                }
            }

            $event->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $event->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            OperationalAlertNotification::notifyAdmins(
                alertType: 'webhook_failed',
                title: "Webhook Processing Failed ({$provider})",
                message: $e->getMessage(),
                severity: 'danger',
                actionUrl: route('admin.system.webhooks'),
                deduplicationKey: "webhook_proc_failed:{$event->id}",
            );
        }

        return [
            'event' => $event->fresh(),
            'duplicate' => false,
        ];
    }
}
