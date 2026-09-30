<?php

namespace App\Services\SocialAuth;

use App\Models\ActivityLog;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Support\Facades\DB;

class CredentialService
{
    public function __construct(protected SocialAuthManager $authManager) {}

    public function storeCredentials(SocialAccount $account, array $tokenData): SocialCredential
    {
        return DB::transaction(function () use ($account, $tokenData) {
            $expiresAt = isset($tokenData['expires_in'])
                ? now()->addSeconds((int) $tokenData['expires_in'])
                : ($tokenData['expires_at'] ?? null);

            $refreshExpiresAt = isset($tokenData['refresh_expires_in'])
                ? now()->addSeconds((int) $tokenData['refresh_expires_in'])
                : ($tokenData['refresh_expires_at'] ?? null);

            $credential = SocialCredential::query()->updateOrCreate(
                ['social_account_id' => $account->id],
                [
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_type' => $tokenData['token_type'] ?? 'Bearer',
                    'expires_at' => $expiresAt,
                    'refresh_expires_at' => $refreshExpiresAt,
                    'scopes' => $tokenData['scopes'] ?? [],
                    'metadata' => $tokenData['metadata'] ?? null,
                    'last_refreshed_at' => now(),
                    'last_verified_at' => now(),
                    'last_refresh_error' => null,
                ]
            );

            $status = $credential->isExpiringSoon()
                ? SocialAccount::CONNECTION_EXPIRING_SOON
                : SocialAccount::CONNECTION_CONNECTED;

            $account->update([
                'connection_status' => $status,
                'connected_at' => $account->connected_at ?? now(),
                'last_verified_at' => now(),
            ]);

            ActivityLog::record(
                action: 'social_account.connected',
                description: "Connected social account {$account->account_name} ({$account->socialPlatform?->name})",
                subject: $account,
                brandId: $account->brand_id,
                properties: ['scopes' => $credential->scopes, 'expires_at' => $credential->expires_at?->toIso8601String()],
            );

            return $credential;
        });
    }

    public function disconnect(SocialAccount $account): void
    {
        DB::transaction(function () use ($account) {
            if ($account->credential) {
                $account->credential->update([
                    'access_token' => null,
                    'refresh_token' => null,
                    'metadata' => null,
                    'expires_at' => null,
                    'refresh_expires_at' => null,
                    'last_refresh_error' => null,
                ]);
            }

            $account->update([
                'connection_status' => SocialAccount::CONNECTION_DISCONNECTED,
            ]);

            ActivityLog::record(
                action: 'social_account.disconnected',
                description: "Disconnected social account {$account->account_name}",
                subject: $account,
                brandId: $account->brand_id,
            );

            OperationalAlertNotification::notifyAdmins(
                alertType: 'social_account_disconnected',
                title: "Social Account Disconnected: {$account->account_name}",
                message: "The {$account->socialPlatform?->name} account '{$account->account_name}' for {$account->brand?->name} was disconnected.",
                severity: 'warning',
                actionUrl: route('admin.social-accounts.index'),
                deduplicationKey: "account_disconnected:{$account->id}",
            );
        });
    }

    public function verifyConnection(SocialAccount $account): bool
    {
        $account->loadMissing(['credential', 'socialPlatform']);
        $credential = $account->credential;

        if (! $credential || empty($credential->access_token)) {
            $account->update(['connection_status' => SocialAccount::CONNECTION_NOT_CONNECTED]);

            return false;
        }

        if ($credential->isExpired()) {
            $account->update(['connection_status' => SocialAccount::CONNECTION_EXPIRED]);

            return false;
        }

        try {
            $provider = $this->authManager->provider($account->socialPlatform->slug);
            $verified = $provider->verifyToken($credential);
        } catch (\Throwable $e) {
            $verified = false;
        }

        if ($verified) {
            $status = $credential->isExpiringSoon()
                ? SocialAccount::CONNECTION_EXPIRING_SOON
                : SocialAccount::CONNECTION_CONNECTED;

            $credential->update(['last_verified_at' => now()]);
            $account->update([
                'connection_status' => $status,
                'last_verified_at' => now(),
            ]);

            return true;
        }

        $account->update(['connection_status' => SocialAccount::CONNECTION_RECONNECT_REQUIRED]);

        return false;
    }
}
