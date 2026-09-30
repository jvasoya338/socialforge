<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\SocialAccountPolicy;
use App\Policies\SocialPostPolicy;
use App\Services\AI\AIProviderManager;
use App\Services\BrandContext;
use App\Services\Publishing\SocialPublisherManager;
use App\Services\SocialAuth\SocialAuthManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BrandContext::class);
        $this->app->singleton(SocialAuthManager::class);
        $this->app->singleton(SocialPublisherManager::class);
        $this->app->singleton(AIProviderManager::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFour();

        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(SocialPost::class, SocialPostPolicy::class);
        Gate::policy(SocialAccount::class, SocialAccountPolicy::class);

        Gate::define('manage-system', fn (User $user) => $user->canManageSystem());
        Gate::define('manage-credentials', fn (User $user) => $user->canManageCredentials());
        Gate::define('approve-posts', fn (User $user) => $user->canApprovePosts());
        Gate::define('publish-posts', fn (User $user) => $user->canPublish());
        Gate::define('edit-content', fn (User $user) => $user->canEditContent());

        RateLimiter::for('content-api', function (Request $request) {
            $source = $request->attributes->get('authenticated_content_source');

            return Limit::perMinute(60)->by($source?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->input('email').'|'.$request->ip());
        });
    }
}
