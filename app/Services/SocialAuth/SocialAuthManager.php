<?php

namespace App\Services\SocialAuth;

use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Services\SocialAuth\Contracts\SocialAuthProviderInterface;
use App\Services\SocialAuth\Providers\FacebookAuthProvider;
use App\Services\SocialAuth\Providers\InstagramAuthProvider;
use App\Services\SocialAuth\Providers\LinkedInAuthProvider;
use App\Services\SocialAuth\Providers\PinterestAuthProvider;
use App\Services\SocialAuth\Providers\XAuthProvider;
use App\Services\SocialAuth\Providers\YouTubeAuthProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SocialAuthManager
{
    /** @var array<string, SocialAuthProviderInterface> */
    protected array $providers = [];

    public function __construct()
    {
        $this->register(new FacebookAuthProvider);
        $this->register(new InstagramAuthProvider);
        $this->register(new XAuthProvider);
        $this->register(new PinterestAuthProvider);
        $this->register(new YouTubeAuthProvider);
        $this->register(new LinkedInAuthProvider);
    }

    public function register(SocialAuthProviderInterface $provider): void
    {
        $this->providers[$provider->platformSlug()] = $provider;
    }

    public function provider(string $slug): SocialAuthProviderInterface
    {
        $normalized = strtolower($slug);

        if (! isset($this->providers[$normalized])) {
            throw new SocialAuthException("Unsupported social OAuth provider: {$slug}", $slug);
        }

        return $this->providers[$normalized];
    }

    public function createOAuthState(Request $request, SocialAccount $account): string
    {
        $nonce = Str::random(40);
        $state = $account->id.':'.$nonce;

        $request->session()->put('social_oauth_state', [
            'state' => $state,
            'social_account_id' => $account->id,
            'platform_slug' => $account->socialPlatform->slug,
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]);

        return $state;
    }

    public function validateOAuthState(Request $request, string $platformSlug, ?string $receivedState): SocialAccount
    {
        $stored = $request->session()->pull('social_oauth_state');

        if (! is_array($stored) || empty($receivedState)) {
            throw new SocialAuthException('Invalid or missing OAuth state parameter.', $platformSlug);
        }

        if (($stored['expires_at'] ?? 0) < now()->timestamp) {
            throw new SocialAuthException('OAuth authorization state has expired. Please try again.', $platformSlug);
        }

        if (! hash_equals((string) ($stored['state'] ?? ''), $receivedState)) {
            throw new SocialAuthException('OAuth state mismatch detected (CSRF protection).', $platformSlug);
        }

        if (($stored['platform_slug'] ?? '') !== strtolower($platformSlug)) {
            throw new SocialAuthException('OAuth platform mismatch detected.', $platformSlug);
        }

        return SocialAccount::with('socialPlatform')->findOrFail($stored['social_account_id']);
    }
}
