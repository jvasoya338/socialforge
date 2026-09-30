<?php

use App\Http\Controllers\Admin\AIController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PublishingController;
use App\Http\Controllers\Admin\SocialPlatformController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Authentication
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// OAuth Callback Route (Outside auth middleware check or inside, but reachable via provider redirect)
Route::get('/admin/social-accounts/oauth/{platform}/callback', [SocialPlatformController::class, 'handleOAuthCallback'])
    ->middleware(['web', 'auth'])
    ->name('admin.social-accounts.oauth.callback');

// Admin Panel
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'brand.context'])
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/switch-brand', [DashboardController::class, 'switchBrand'])->name('switch-brand');

        // BRANDS
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::post('/brands/{brand}/toggle', [BrandController::class, 'toggleStatus'])->name('brands.toggle');

        // CONTENT
        Route::get('/content-sources', [ContentController::class, 'sources'])->name('content.sources');
        Route::post('/content-sources', [ContentController::class, 'storeSource'])->name('content.sources.store');
        Route::post('/content-sources/{source}/rotate-key', [ContentController::class, 'rotateSourceKey'])->name('content.sources.rotate-key');
        Route::post('/content-sources/{source}/revoke-key', [ContentController::class, 'revokeSourceKey'])->name('content.sources.revoke-key');

        Route::get('/content', [ContentController::class, 'index'])->name('content.index');
        Route::post('/content', [ContentController::class, 'store'])->name('content.store');
        Route::get('/content/{content}', [ContentController::class, 'show'])->name('content.show');

        Route::get('/media-library', [ContentController::class, 'mediaLibrary'])->name('content.media');
        Route::post('/media-library/upload', [ContentController::class, 'uploadMedia'])->name('content.media.upload');

        // SOCIAL
        Route::get('/social-platforms', [SocialPlatformController::class, 'platforms'])->name('social-platforms.index');
        Route::post('/social-platforms/{platform}/toggle', [SocialPlatformController::class, 'togglePlatform'])->name('social-platforms.toggle');

        Route::get('/social-accounts', [SocialPlatformController::class, 'accounts'])->name('social-accounts.index');
        Route::post('/social-accounts', [SocialPlatformController::class, 'storeAccount'])->name('social-accounts.store');
        Route::get('/social-connections', [SocialPlatformController::class, 'connections'])->name('social-accounts.connections');
        Route::get('/credential-health', [SocialPlatformController::class, 'credentialHealth'])->name('credential-health.index');

        Route::get('/social-accounts/{account}/oauth', [SocialPlatformController::class, 'initiateOAuth'])->name('social-accounts.oauth.initiate');
        Route::post('/social-accounts/{account}/disconnect', [SocialPlatformController::class, 'disconnect'])->name('social-accounts.disconnect');
        Route::post('/social-accounts/{account}/verify', [SocialPlatformController::class, 'verifyConnection'])->name('social-accounts.verify');
        Route::post('/social-accounts/{account}/refresh', [SocialPlatformController::class, 'refreshToken'])->name('social-accounts.refresh');

        // PUBLISHING
        Route::get('/publishing/drafts', [PublishingController::class, 'drafts'])->name('publishing.drafts');
        Route::get('/publishing/approval-queue', [PublishingController::class, 'approvalQueue'])->name('publishing.approval-queue');
        Route::get('/publishing/calendar', [PublishingController::class, 'calendar'])->name('publishing.calendar');
        Route::get('/publishing/scheduled', [PublishingController::class, 'scheduled'])->name('publishing.scheduled');
        Route::get('/publishing/queue', [PublishingController::class, 'queue'])->name('publishing.queue');
        Route::get('/publishing/published', [PublishingController::class, 'published'])->name('publishing.published');
        Route::get('/publishing/failed', [PublishingController::class, 'failed'])->name('publishing.failed');

        Route::post('/publishing/posts', [PublishingController::class, 'store'])->name('publishing.posts.store');
        Route::put('/publishing/posts/{post}', [PublishingController::class, 'update'])->name('publishing.posts.update');
        Route::post('/publishing/posts/{post}/approve', [PublishingController::class, 'approve'])->name('publishing.posts.approve');
        Route::post('/publishing/posts/{post}/reject', [PublishingController::class, 'reject'])->name('publishing.posts.reject');
        Route::post('/publishing/posts/{post}/schedule', [PublishingController::class, 'schedule'])->name('publishing.posts.schedule');
        Route::post('/publishing/posts/{post}/publish-now', [PublishingController::class, 'publishNow'])->name('publishing.posts.publish-now');
        Route::post('/publishing/posts/{post}/retry', [PublishingController::class, 'retry'])->name('publishing.posts.retry');
        Route::post('/publishing/posts/{post}/duplicate', [PublishingController::class, 'duplicate'])->name('publishing.posts.duplicate');
        Route::post('/publishing/posts/{post}/cancel', [PublishingController::class, 'cancel'])->name('publishing.posts.cancel');
        Route::post('/publishing/posts/{post}/humanize', [PublishingController::class, 'humanizePost'])->name('publishing.posts.humanize');

        // AI
        Route::get('/ai/generator', [AIController::class, 'generator'])->name('ai.generator');
        Route::post('/ai/generator/run', [AIController::class, 'runGeneration'])->name('ai.generator.run');
        Route::get('/ai/providers', [AIController::class, 'providers'])->name('ai.providers.index');
        Route::post('/ai/providers', [AIController::class, 'storeProvider'])->name('ai.providers.store');
        Route::post('/ai/providers/{provider}/test', [AIController::class, 'testProvider'])->name('ai.providers.test');
        Route::get('/ai/brand-voice', [AIController::class, 'brandVoice'])->name('ai.brand-voice.index');
        Route::put('/ai/brand-voice/{brand}', [AIController::class, 'updateBrandVoice'])->name('ai.brand-voice.update');
        Route::post('/ai/brand-voice/{brand}/examples', [AIController::class, 'storeExample'])->name('ai.brand-voice.examples.store');
        Route::post('/ai/brand-voice/examples/{example}/toggle', [AIController::class, 'toggleExampleApproved'])->name('ai.brand-voice.examples.toggle');
        Route::delete('/ai/brand-voice/examples/{example}', [AIController::class, 'deleteExample'])->name('ai.brand-voice.examples.delete');
        Route::get('/ai/prompts', [AIController::class, 'prompts'])->name('ai.prompts.index');
        Route::put('/ai/prompts/{prompt}', [AIController::class, 'updatePrompt'])->name('ai.prompts.update');
        Route::get('/ai/history', [AIController::class, 'history'])->name('ai.history.index');

        // ANALYTICS
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('/analytics/brand', [AnalyticsController::class, 'brand'])->name('analytics.brand');
        Route::get('/analytics/platform', [AnalyticsController::class, 'platform'])->name('analytics.platform');
        Route::get('/analytics/content', [AnalyticsController::class, 'content'])->name('analytics.content');

        // SYSTEM
        Route::get('/system/users', [SystemController::class, 'users'])->name('system.users');
        Route::post('/system/users', [SystemController::class, 'storeUser'])->name('system.users.store');
        Route::put('/system/users/{user}', [SystemController::class, 'updateUser'])->name('system.users.update');

        Route::get('/system/notifications', [SystemController::class, 'notifications'])->name('system.notifications');
        Route::post('/system/notifications/{id}/read', [SystemController::class, 'markNotificationRead'])->name('system.notifications.read');
        Route::post('/system/notifications/read-all', [SystemController::class, 'markAllNotificationsRead'])->name('system.notifications.read-all');

        Route::get('/system/webhooks', [SystemController::class, 'webhooks'])->name('system.webhooks');
        Route::get('/system/activity-logs', [SystemController::class, 'activityLogs'])->name('system.activity-logs');

        Route::get('/system/settings', [SystemController::class, 'settings'])->name('system.settings');
        Route::post('/system/settings', [SystemController::class, 'updateSettings'])->name('system.settings.update');

        Route::get('/system/queue-health', [SystemController::class, 'queueHealth'])->name('system.queue-health');
        Route::post('/system/queue-health/retry/{id}', [SystemController::class, 'retryFailedJob'])->name('system.queue-health.retry');
        Route::post('/system/queue-health/flush', [SystemController::class, 'flushFailedJobs'])->name('system.queue-health.flush');

        Route::get('/system/system-health', [SystemController::class, 'systemHealth'])->name('system.system-health');
    });
