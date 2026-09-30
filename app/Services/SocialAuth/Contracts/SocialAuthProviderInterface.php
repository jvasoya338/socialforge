<?php

namespace App\Services\SocialAuth\Contracts;

use App\Models\SocialAccount;
use App\Models\SocialCredential;

interface SocialAuthProviderInterface
{
    public function platformSlug(): string;

    public function hasConfiguredAppCredentials(): bool;

    public function getAuthorizationUrl(SocialAccount $account, string $state): string;

    /**
     * Exchange OAuth authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token?: string|null, expires_in?: int|null, refresh_expires_in?: int|null, scopes?: array<int, string>, token_type?: string, metadata?: array<string, mixed>}
     */
    public function exchangeCode(string $code): array;

    /**
     * Refresh an existing credential token if supported by the platform.
     *
     * @return array{access_token: string, refresh_token?: string|null, expires_in?: int|null, refresh_expires_in?: int|null, scopes?: array<int, string>}
     */
    public function refreshToken(SocialCredential $credential): array;

    /**
     * Verify connection health with the provider API.
     */
    public function verifyToken(SocialCredential $credential): bool;

    public function supportsAutomaticRefresh(): bool;
}
