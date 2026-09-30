<?php

namespace App\Services\SocialAuth\Providers;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\SocialCredential;
use App\Services\SocialAuth\AbstractSocialAuthProvider;
use Illuminate\Support\Facades\Http;

class YouTubeAuthProvider extends AbstractSocialAuthProvider
{
    public function platformSlug(): string
    {
        return 'youtube';
    }

    protected function buildAuthorizationUrl(SocialAccount $account, string $state): string
    {
        return $this->buildQueryUrl('https://accounts.google.com/o/oauth2/v2/auth', [
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', $this->config('scopes', [])),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $this->ensureAppCredentialsConfigured();

        $response = Http::timeout(15)->asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->config('client_id'),
            'client_secret' => $this->config('client_secret'),
            'redirect_uri' => $this->config('redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'YouTube token exchange failed: '.($response->json('error_description') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: true,
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'scopes' => isset($data['scope']) ? explode(' ', (string) $data['scope']) : $this->config('scopes', []),
            'token_type' => 'Bearer',
        ];
    }

    public function refreshToken(SocialCredential $credential): array
    {
        $this->ensureAppCredentialsConfigured();

        if (empty($credential->refresh_token)) {
            throw new SocialAuthException('Missing YouTube refresh token; reconnect required.', $this->platformSlug(), true);
        }

        $response = Http::timeout(15)->asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->config('client_id'),
            'client_secret' => $this->config('client_secret'),
            'refresh_token' => $credential->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new SocialAuthException(
                'YouTube token refresh failed: '.($response->json('error_description') ?? $response->status()),
                provider: $this->platformSlug(),
                requiresReconnect: in_array($response->status(), [400, 401], true),
            );
        }

        $data = $response->json();

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $data['refresh_token'] ?? $credential->refresh_token,
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
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
            ->get('https://www.googleapis.com/youtube/v3/channels', ['part' => 'id', 'mine' => 'true']);

        return $response->successful();
    }
}
