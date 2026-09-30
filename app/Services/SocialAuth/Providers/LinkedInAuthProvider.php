<?php

namespace App\Services\SocialAuth\Providers;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Services\SocialAuth\AbstractSocialAuthProvider;
use Illuminate\Support\Facades\Http;

class LinkedInAuthProvider extends AbstractSocialAuthProvider
{
    public function platformSlug(): string
    {
        return 'linkedin';
    }

    protected function buildAuthorizationUrl(SocialAccount $account, string $state): string
    {
        return $this->buildQueryUrl('https://www.linkedin.com/oauth/v2/authorization', [
            'response_type' => 'code',
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'state' => $state,
            'scope' => implode(' ', $this->config('scopes', ['openid', 'profile', 'w_member_social'])),
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $this->ensureAppCredentialsConfigured();

        $response = Http::timeout(15)->asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config('client_id'),
            'client_secret' => $this->config('client_secret'),
            'redirect_uri' => $this->config('redirect_uri'),
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'LinkedIn token exchange failed: '.($response->json('error_description') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => (int) ($data['expires_in'] ?? 5184000),
            'refresh_expires_in' => isset($data['refresh_token_expires_in']) ? (int) $data['refresh_token_expires_in'] : null,
            'scopes' => isset($data['scope']) ? explode(' ', (string) $data['scope']) : $this->config('scopes', []),
            'token_type' => 'Bearer',
        ];
    }

    public function refreshToken(SocialCredential $credential): array
    {
        $this->ensureAppCredentialsConfigured();

        if (empty($credential->refresh_token)) {
            throw new SocialAuthException('LinkedIn account has no refresh token and requires manual reconnection.', $this->platformSlug(), true);
        }

        $response = Http::timeout(15)->asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credential->refresh_token,
            'client_id' => $this->config('client_id'),
            'client_secret' => $this->config('client_secret'),
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'LinkedIn token refresh failed: '.($response->json('error_description') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? $credential->refresh_token,
            'expires_in' => (int) ($data['expires_in'] ?? 5184000),
            'scopes' => $credential->scopes ?? [],
        ];
    }

    public function verifyToken(SocialCredential $credential): bool
    {
        if (! $credential->hasValidAccessToken()) {
            return false;
        }

        $response = Http::timeout(10)
            ->withToken($credential->access_token)
            ->get('https://api.linkedin.com/v2/userinfo');

        return $response->successful();
    }
}
