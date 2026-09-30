<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(protected WebhookService $webhookService) {}

    public function handle(string $provider, Request $request): JsonResponse
    {
        $result = $this->webhookService->handleIncomingWebhook($provider, $request);
        $event = $result['event'];

        if (! $event->signature_valid) {
            return response()->json([
                'status' => 'rejected',
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        return response()->json([
            'status' => $event->status === WebhookEvent::STATUS_PROCESSED ? 'processed' : $event->status,
            'duplicate' => $result['duplicate'],
            'event_id' => $event->id,
        ], 200);
    }
}
