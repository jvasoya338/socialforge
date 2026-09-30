<?php

namespace App\Services\SocialAuth\Providers;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Services\SocialAuth\AbstractSocialAuthProvider;
use Illuminate\Support\Facades\Http;

class XAuthProvider extends AbstractSocialAuthProvider
{
    public function platformSlug(): string
    {
        return 'x';
    }

    protected function buildAuthorizationUrl(SocialAccount $account, string $state): string
    {
        return $this->buildQueryUrl('https://twitter.com/i/oauth2/authorize', [
            'response_type' => 'code',
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'scope' => implode(' ', $this->config('scopes', ['tweet.read', 'tweet.write', 'users.read', 'offline.access'])),
            'state' => $state,
            'code_challenge' => 'socialforge_pkce_challenge',
            'code_challenge_method' => 'plain',
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $this->ensureAppCredentialsConfigured();

        $response = Http::timeout(15)
            ->withBasicAuth((string) $this->config('client_id'), (string) $this->config('client_secret'))
            ->asForm()
            ->post('https://api.x.com/2/oauth2/token', [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->config('redirect_uri'),
                'code_verifier' => 'socialforge_pkce_challenge',
            ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'X OAuth token exchange failed: '.($response->json('error_description') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => (int) ($data['expires_in'] ?? 7200),
            'scopes' => isset($data['scope']) ? explode(' ', (string) $data['scope']) : $this->config('scopes', []),
            'token_type' => (string) ($data['token_type'] ?? 'Bearer'),
        ];
    }

    public function refreshToken(SocialCredential $credential): array
    {
        $this->ensureAppCredentialsConfigured();

        if (empty($credential->refresh_token)) {
            throw new SocialAuthException('Missing X refresh token; account requires reconnection.', $this->platformSlug(), true);
        }

        $response = Http::timeout(15)
            ->withBasicAuth((string) $this->config('client_id'), (string) $this->config('client_secret'))
            ->asForm()
            ->post('https://api.x.com/2/oauth2/token', [
                'refresh_token' => $credential->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'X token refresh failed: '.($response->json('error_description') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: in_array($response->status(), [400, 401], true),
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? $credential->refresh_token,
            'expires_in' => (int) ($data['expires_in'] ?? 7200),
            'scopes' => isset($data['scope']) ? explode(' ', (string) $data['scope']) : ($credential->scopes ?? []),
        ];
    }

    public function verifyToken(SocialCredential $credential): bool
    {
        if (! $credential->hasValidAccessToken()) {
            return false;
        }

        $response = Http::timeout(10)
            ->withToken($credential->access_token)
            ->get('https://api.x.com/2/users/me');

        return $response->successful();
    }
}
