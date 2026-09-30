<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSocialAccountRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use App\Services\BrandContext;
use App\Services\SocialAuth\CredentialService;
use App\Services\SocialAuth\SocialAuthManager;
use App\Services\SocialAuth\TokenRefreshService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SocialPlatformController extends Controller
{
    public function __construct(
        protected BrandContext $brandContext,
        protected SocialAuthManager $authManager,
        protected CredentialService $credentialService,
        protected TokenRefreshService $tokenRefreshService,
    ) {}

    public function platforms(): View
    {
        $platforms = SocialPlatform::withCount(['socialAccounts', 'socialPosts'])->get();

        $appConfigStatus = [];
        foreach ($platforms as $platform) {
            try {
                $appConfigStatus[$platform->slug] = $this->authManager->provider($platform->slug)->hasConfiguredAppCredentials();
            } catch (\Throwable) {
                $appConfigStatus[$platform->slug] = false;
            }
        }

        return view('admin.social.platforms', compact('platforms', 'appConfigStatus'));
    }

    public function togglePlatform(SocialPlatform $platform): RedirectResponse
    {
        Gate::authorize('manage-system');

        $newStatus = $platform->status === SocialPlatform::STATUS_ACTIVE
            ? SocialPlatform::STATUS_INACTIVE
            : SocialPlatform::STATUS_ACTIVE;

        $platform->update(['status' => $newStatus]);

        ActivityLog::record(
            action: 'social_platform.status_toggled',
            description: "Set social platform '{$platform->name}' status to {$newStatus}",
            subject: $platform,
        );

        return back()->with('success', "Platform '{$platform->name}' is now {$newStatus}.");
    }

    public function accounts(Request $request): View
    {
        $query = $this->brandContext->apply(
            SocialAccount::with(['brand', 'socialPlatform', 'credential']),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($platformId = $request->integer('platform_id')) {
            $query->where('social_platform_id', $platformId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('brand_id')->paginate(15)->withQueryString();
        $brands = Brand::orderBy('name')->get();
        $platforms = SocialPlatform::orderBy('name')->get();

        return view('admin.social.accounts', compact('accounts', 'brands', 'platforms'));
    }

    public function storeAccount(StoreSocialAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['connection_status'] = SocialAccount::CONNECTION_NOT_CONNECTED;

        $account = SocialAccount::create($data);

        ActivityLog::record(
            action: 'social_account.created',
            description: "Created social account '{$account->account_name}'",
            subject: $account,
            brandId: $account->brand_id,
        );

        return back()->with('success', "Social account '{$account->account_name}' created (status: Not Connected).");
    }

    public function connections(Request $request): View
    {
        $accounts = $this->brandContext->apply(
            SocialAccount::with(['brand', 'socialPlatform', 'credential']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->orderBy('brand_id')->get();

        return view('admin.social.connections', compact('accounts'));
    }

    public function credentialHealth(Request $request): View
    {
        $query = $this->brandContext->apply(
            SocialAccount::with(['brand', 'socialPlatform', 'credential']),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($status = $request->input('connection_status')) {
            $query->where('connection_status', $status);
        }

        $accounts = $query->orderBy('brand_id')->paginate(20)->withQueryString();

        return view('admin.social.credential-health', compact('accounts'));
    }

    public function initiateOAuth(Request $request, SocialAccount $account): RedirectResponse
    {
        Gate::authorize('manageCredentials', $account);
        $account->loadMissing('socialPlatform');

        try {
            $provider = $this->authManager->provider($account->socialPlatform->slug);
            $state = $this->authManager->createOAuthState($request, $account);
            $url = $provider->getAuthorizationUrl($account, $state);

            return redirect()->away($url);
        } catch (SocialAuthException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function handleOAuthCallback(Request $request, string $platform): RedirectResponse
    {
        Gate::authorize('manage-credentials');

        try {
            $account = $this->authManager->validateOAuthState($request, $platform, $request->query('state'));

            if ($request->filled('error')) {
                throw new SocialAuthException(
                    'OAuth authorization denied by provider: '.$request->query('error_description', $request->query('error')),
                    provider: $platform
                );
            }

            $code = (string) $request->query('code', '');
            if ($code === '') {
                throw new SocialAuthException('Missing OAuth authorization code.', $platform);
            }

            $provider = $this->authManager->provider($platform);
            $tokenData = $provider->exchangeCode($code);

            $this->credentialService->storeCredentials($account, $tokenData);

            return redirect()->route('admin.credential-health.index')
                ->with('success', "Successfully connected {$account->account_name} via OAuth.");
        } catch (SocialAuthException $e) {
            return redirect()->route('admin.credential-health.index')
                ->with('error', $e->getMessage());
        }
    }

    public function disconnect(SocialAccount $account): RedirectResponse
    {
        Gate::authorize('manageCredentials', $account);

        $this->credentialService->disconnect($account);

        return back()->with('success', "Disconnected {$account->account_name} and purged stored tokens.");
    }

    public function verifyConnection(SocialAccount $account): RedirectResponse
    {
        Gate::authorize('manageCredentials', $account);

        $verified = $this->credentialService->verifyConnection($account);

        if ($verified) {
            return back()->with('success', "Verified active connection for {$account->account_name}.");
        }

        return back()->with('error', "Connection verification failed for {$account->account_name} ({$account->fresh()->healthLabel()}).");
    }

    public function refreshToken(SocialAccount $account): RedirectResponse
    {
        Gate::authorize('manageCredentials', $account);

        $refreshed = $this->tokenRefreshService->refreshAccountToken($account);

        if ($refreshed) {
            return back()->with('success', "Successfully refreshed token for {$account->account_name}.");
        }

        return back()->with('error', "Could not refresh token for {$account->account_name}. Status: {$account->fresh()->healthLabel()}.");
    }
}
