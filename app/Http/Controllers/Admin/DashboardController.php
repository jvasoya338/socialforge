<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AIGeneration;
use App\Models\Brand;
use App\Models\PublishingAttempt;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\BrandContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected BrandContext $brandContext) {}

    public function index(): View
    {
        $brandId = $this->brandContext->id();

        $stats = [
            'total_brands' => Brand::count(),
            'connected_accounts' => $this->brandContext->apply(SocialAccount::query())
                ->whereIn('connection_status', [SocialAccount::CONNECTION_CONNECTED, SocialAccount::CONNECTION_EXPIRING_SOON])
                ->count(),
            'draft_posts' => $this->brandContext->apply(SocialPost::query())
                ->where('status', SocialPost::STATUS_DRAFT)
                ->count(),
            'awaiting_approval' => $this->brandContext->apply(SocialPost::query())
                ->where('status', SocialPost::STATUS_PENDING_APPROVAL)
                ->count(),
            'scheduled_today' => $this->brandContext->apply(SocialPost::query())
                ->where('status', SocialPost::STATUS_SCHEDULED)
                ->whereDate('scheduled_at', today())
                ->count(),
            'published_today' => $this->brandContext->apply(SocialPost::query())
                ->where('status', SocialPost::STATUS_PUBLISHED)
                ->whereDate('published_at', today())
                ->count(),
            'failed_posts' => $this->brandContext->apply(SocialPost::query())
                ->where('status', SocialPost::STATUS_FAILED)
                ->count(),
            'credential_problems' => $this->brandContext->apply(SocialAccount::query())
                ->whereIn('connection_status', [
                    SocialAccount::CONNECTION_EXPIRING_SOON,
                    SocialAccount::CONNECTION_REFRESH_FAILED,
                    SocialAccount::CONNECTION_EXPIRED,
                    SocialAccount::CONNECTION_RECONNECT_REQUIRED,
                ])
                ->count(),
        ];

        $upcomingScheduled = $this->brandContext->apply(SocialPost::with(['brand', 'socialPlatform', 'socialAccount']))
            ->where('status', SocialPost::STATUS_SCHEDULED)
            ->orderBy('scheduled_at')
            ->limit(6)
            ->get();

        $recentPublishingAttempts = PublishingAttempt::with(['socialPost.brand', 'socialPost.socialPlatform', 'socialAccount'])
            ->when($brandId, fn ($q) => $q->whereHas('socialPost', fn ($sq) => $sq->where('brand_id', $brandId)))
            ->latest()
            ->limit(6)
            ->get();

        $failedAttempts = PublishingAttempt::with(['socialPost.brand', 'socialPost.socialPlatform', 'socialAccount'])
            ->where('status', PublishingAttempt::STATUS_FAILED)
            ->when($brandId, fn ($q) => $q->whereHas('socialPost', fn ($sq) => $sq->where('brand_id', $brandId)))
            ->latest()
            ->limit(5)
            ->get();

        $socialHealth = $this->brandContext->apply(SocialAccount::with(['brand', 'socialPlatform', 'credential']))
            ->orderBy('brand_id')
            ->limit(8)
            ->get();

        $aiUsage = [
            'total_generations' => $this->brandContext->apply(AIGeneration::query())->count(),
            'completed_today' => $this->brandContext->apply(AIGeneration::query())
                ->whereDate('created_at', today())
                ->where('status', AIGeneration::STATUS_COMPLETED)
                ->count(),
            'total_tokens' => (int) $this->brandContext->apply(AIGeneration::query())->sum('input_tokens')
                + (int) $this->brandContext->apply(AIGeneration::query())->sum('output_tokens'),
            'estimated_cost' => (float) $this->brandContext->apply(AIGeneration::query())->sum('estimated_cost'),
        ];

        $recentActivity = $this->brandContext->apply(ActivityLog::with(['user', 'brand']))
            ->latest()
            ->limit(8)
            ->get();

        $brandOverview = Brand::withCount([
            'contents',
            'socialAccounts',
            'socialPosts as published_posts_count' => fn ($q) => $q->where('status', SocialPost::STATUS_PUBLISHED),
            'socialPosts as pending_posts_count' => fn ($q) => $q->where('status', SocialPost::STATUS_PENDING_APPROVAL),
        ])->orderBy('name')->get();

        return view('admin.dashboard', compact(
            'stats',
            'upcomingScheduled',
            'recentPublishingAttempts',
            'failedAttempts',
            'socialHealth',
            'aiUsage',
            'recentActivity',
            'brandOverview'
        ));
    }

    public function switchBrand(Request $request): RedirectResponse
    {
        $brandId = $request->input('brand_id');

        if ($brandId === 'all' || empty($brandId)) {
            $request->session()->forget('selected_brand_id');
            if ($request->user()) {
                $request->user()->update(['current_brand_id' => null]);
            }

            return back()->with('success', 'Switched context to All Brands.');
        }

        $brand = Brand::findOrFail($brandId);
        $request->session()->put('selected_brand_id', $brand->id);

        if ($request->user()) {
            $request->user()->update(['current_brand_id' => $brand->id]);
        }

        return back()->with('success', "Switched active brand context to {$brand->name}.");
    }
}
