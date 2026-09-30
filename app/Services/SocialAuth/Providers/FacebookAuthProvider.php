<?php

namespace App\Services\SocialAuth\Providers;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Services\SocialAuth\AbstractSocialAuthProvider;
use Illuminate\Support\Facades\Http;

class FacebookAuthProvider extends AbstractSocialAuthProvider
{
    public function platformSlug(): string
    {
        return 'facebook';
    }

    protected function buildAuthorizationUrl(SocialAccount $account, string $state): string
    {
        return $this->buildQueryUrl('https://www.facebook.com/v20.0/dialog/oauth', [
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'state' => $state,
            'scope' => implode(',', $this->config('scopes', ['pages_manage_posts', 'pages_read_engagement'])),
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
                'Facebook token exchange failed: '.($response->json('error.message') ?? $response->status()),
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
            'token_type' => (string) ($data['token_type'] ?? 'Bearer'),
        ];
    }

    public function refreshToken(SocialCredential $credential): array
    {
        $this->ensureAppCredentialsConfigured();

        if (empty($credential->access_token)) {
            throw new SocialAuthException('Missing Facebook token for long-lived token exchange.', $this->platformSlug(), true);
        }

        $response = Http::timeout(15)->get('https://graph.facebook.com/v20.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->config('client_id'),
            'client_secret' => $this->config('client_secret'),
            'fb_exchange_token' => $credential->access_token,
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'Facebook token refresh failed: '.($response->json('error.message') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: $response->status() === 400 || $response->status() === 401,
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
