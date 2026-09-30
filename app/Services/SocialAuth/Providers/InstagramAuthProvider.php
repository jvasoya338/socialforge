<?php

namespace App\Services\SocialAuth\Providers;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Services\SocialAuth\AbstractSocialAuthProvider;
use Illuminate\Support\Facades\Http;

class InstagramAuthProvider extends AbstractSocialAuthProvider
{
    public function platformSlug(): string
    {
        return 'instagram';
    }

    protected function buildAuthorizationUrl(SocialAccount $account, string $state): string
    {
        return $this->buildQueryUrl('https://www.facebook.com/v20.0/dialog/oauth', [
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'state' => $state,
            'scope' => implode(',', $this->config('scopes', ['instagram_basic', 'instagram_content_publish'])),
            'response_type' => 'code',
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $this->ensureAppCredentialsConfigured();

        $response = Http::timeout(15)->get('https://graph.facebook.com/v20.0/oauth/access_token', [
            'client_id' => $this->config('client_id'),
            'client_secret' => $this->config('client_secret'),
            'redirect_uri' => $this->config('redirect_uri'),
            'code' => $code,
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'Instagram token exchange failed: '.($response->json('error.message') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['access_token'] ?? null,
            'expires_in' => (int) ($data['expires_in'] ?? 5184000),
            'scopes' => $this->config('scopes', []),
            'token_type' => 'Bearer',
        ];
    }

    public function refreshToken(SocialCredential $credential): array
    {
        if (empty($credential->access_token)) {
            throw new SocialAuthException('Missing Instagram access token for refresh.', $this->platformSlug(), true);
        }

        $response = Http::timeout(15)->get('https://graph.instagram.com/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $credential->access_token,
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'Instagram token refresh failed: '.($response->json('error.message') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['access_token'] ?? $credential->refresh_token,
            'expires_in' => (int) ($data['expires_in'] ?? 5184000),
            'scopes' => $credential->scopes ?? $this->config('scopes', []),
        ];
    }

    public function verifyToken(SocialCredential $credential): bool
    {
        if (! $credential->hasValidAccessToken()) {
            return false;
        }

        $response = Http::timeout(10)
            ->withToken($credential->access_token)
            ->get('https://graph.facebook.com/v20.0/me', ['fields' => 'id,name']);

        return $response->successful();
    }
}
