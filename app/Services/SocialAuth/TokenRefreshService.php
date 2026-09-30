<?php

namespace App\Services\SocialAuth;

use App\Exceptions\SocialAuthException;
use App\Models\ActivityLog;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Models\SystemSetting;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Support\Collection;

class TokenRefreshService
{
    public function __construct(
        protected SocialAuthManager $authManager,
        protected CredentialService $credentialService,
    ) {}

    /**
     * Find credentials that are expiring within threshold hours or already marked expiring_soon.
     */
    public function getExpiringCredentials(?int $thresholdHours = null): Collection
    {
        $hours = $thresholdHours ?? (int) SystemSetting::getValue('token_refresh_threshold_hours', 48);

        return SocialCredential::with(['socialAccount.socialPlatform', 'socialAccount.brand'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addHours($hours))
            ->get();
    }

    public function refreshAccountToken(SocialAccount $account): bool
    {
        $account->loadMissing(['credential', 'socialPlatform', 'brand']);
        $credential = $account->credential;

        if (! $credential || ! $credential->canRefresh()) {
            $account->update(['connection_status' => SocialAccount::CONNECTION_RECONNECT_REQUIRED]);
            if ($credential) {
                $credential->update(['last_refresh_error' => 'No valid refresh token available; manual reconnection required.']);
            }

            OperationalAlertNotification::notifyAdmins(
                alertType: 'reconnect_required',
                title: "Reconnect Required: {$account->account_name}",
                message: "Token for {$account->account_name} ({$account->socialPlatform?->name}) cannot be refreshed automatically and requires manual reconnection.",
                severity: 'danger',
                actionUrl: route('admin.credential-health.index'),
                deduplicationKey: "reconnect_required:{$account->id}",
            );

            return false;
        }

        $provider = $this->authManager->provider($account->socialPlatform->slug);

        if (! $provider->supportsAutomaticRefresh()) {
            $account->update(['connection_status' => SocialAccount::CONNECTION_RECONNECT_REQUIRED]);
            $credential->update(['last_refresh_error' => 'Platform does not support automatic token refresh.']);

            return false;
        }

        try {
            $tokenData = $provider->refreshToken($credential);
            $this->credentialService->storeCredentials($account, $tokenData);

            ActivityLog::record(
                action: 'credential.refreshed',
                description: "Refreshed token for {$account->account_name}",
                subject: $account,
                brandId: $account->brand_id,
            );

            return true;
        } catch (SocialAuthException $e) {
            $newStatus = $e->requiresReconnect
                ? SocialAccount::CONNECTION_RECONNECT_REQUIRED
                : SocialAccount::CONNECTION_REFRESH_FAILED;

            $account->update(['connection_status' => $newStatus]);
            $credential->update(['last_refresh_error' => $e->getMessage()]);

            ActivityLog::record(
                action: 'credential.refresh_failed',
                description: "Failed to refresh token for {$account->account_name}: {$e->getMessage()}",
                subject: $account,
                brandId: $account->brand_id,
            );

            OperationalAlertNotification::notifyAdmins(
                alertType: $e->requiresReconnect ? 'reconnect_required' : 'token_refresh_failed',
                title: "Token Refresh Failed: {$account->account_name}",
                message: $e->getMessage(),
                severity: 'danger',
                actionUrl: route('admin.credential-health.index'),
                deduplicationKey: "token_refresh_failed:{$account->id}",
            );

            return false;
        } catch (\Throwable $e) {
            $account->update(['connection_status' => SocialAccount::CONNECTION_REFRESH_FAILED]);
            $credential->update(['last_refresh_error' => $e->getMessage()]);

            return false;
        }
    }
}
