<?php

namespace App\Services\SocialAuth\Providers;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Services\SocialAuth\AbstractSocialAuthProvider;
use Illuminate\Support\Facades\Http;

class PinterestAuthProvider extends AbstractSocialAuthProvider
{
    public function platformSlug(): string
    {
        return 'pinterest';
    }

    protected function buildAuthorizationUrl(SocialAccount $account, string $state): string
    {
        return $this->buildQueryUrl('https://www.pinterest.com/oauth/', [
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(',', $this->config('scopes', ['boards:read', 'pins:read', 'pins:write'])),
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $this->ensureAppCredentialsConfigured();

        $response = Http::timeout(15)
            ->withBasicAuth((string) $this->config('client_id'), (string) $this->config('client_secret'))
            ->asForm()
            ->post('https://api.pinterest.com/v5/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->config('redirect_uri'),
            ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'Pinterest token exchange failed: '.($response->json('message') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => (int) ($data['expires_in'] ?? 2592000),
            'refresh_expires_in' => (int) ($data['refresh_token_expires_in'] ?? 31536000),
            'scopes' => isset($data['scope']) ? explode(' ', (string) $data['scope']) : $this->config('scopes', []),
            'token_type' => 'Bearer',
        ];
    }

    public function refreshToken(SocialCredential $credential): array
    {
        $this->ensureAppCredentialsConfigured();

        if (empty($credential->refresh_token)) {
            throw new SocialAuthException('Missing Pinterest refresh token.', $this->platformSlug(), true);
        }

        $response = Http::timeout(15)
            ->withBasicAuth((string) $this->config('client_id'), (string) $this->config('client_secret'))
            ->asForm()
            ->post('https://api.pinterest.com/v5/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $credential->refresh_token,
            ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'Pinterest token refresh failed: '.($response->json('message') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: in_array($response->status(), [400, 401], true),
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? $credential->refresh_token,
            'expires_in' => (int) ($data['expires_in'] ?? 2592000),
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
            ->get('https://api.pinterest.com/v5/user_account');

        return $response->successful();
    }
}
