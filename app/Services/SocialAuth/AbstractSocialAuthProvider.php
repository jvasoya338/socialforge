<?php

namespace App\Services\SocialAuth;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Services\SocialAuth\Contracts\SocialAuthProviderInterface;

abstract class AbstractSocialAuthProvider implements SocialAuthProviderInterface
{
    protected function config(string $key, mixed $default = null): mixed
    {
        return config("services.social_platforms.{$this->platformSlug()}.{$key}", $default);
    }

    public function hasConfiguredAppCredentials(): bool
    {
        return ! empty($this->config('client_id')) && ! empty($this->config('client_secret'));
    }

    protected function ensureAppCredentialsConfigured(): void
    {
        if (! $this->hasConfiguredAppCredentials()) {
            throw new SocialAuthException(
                "Application OAuth credentials (CLIENT_ID / CLIENT_SECRET) are not configured for {$this->platformSlug()}.",
                provider: $this->platformSlug(),
                requiresReconnect: false,
            );
        }
    }

    public function supportsAutomaticRefresh(): bool
    {
        return true;
    }

    protected function buildQueryUrl(string $baseUrl, array $params): string
    {
        return $baseUrl.'?'.http_build_query($params);
    }

    public function getAuthorizationUrl(SocialAccount $account, string $state): string
    {
        $this->ensureAppCredentialsConfigured();

        return $this->buildAuthorizationUrl($account, $state);
    }

    abstract protected function buildAuthorizationUrl(SocialAccount $account, string $state): string;
}
