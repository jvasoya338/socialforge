<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\SocialPlatform;
use App\Models\SocialPost;
use App\Services\AnalyticsService;
use App\Services\BrandContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        protected BrandContext $brandContext,
        protected AnalyticsService $analyticsService,
    ) {}

    public function overview(Request $request): View
    {
        $brandId = $request->integer('brand_id') ?: $this->brandContext->id();
        $metrics = $this->analyticsService->getOverviewMetrics(
            brandId: $brandId,
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date')
        );

        $brands = Brand::orderBy('name')->get();
        $platforms = SocialPlatform::active()->get();

        $recentPublished = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'analytics']),
            'brand_id',
            $brandId
        )->where('status', SocialPost::STATUS_PUBLISHED)
            ->latest('published_at')
            ->limit(10)
            ->get();

        return view('admin.analytics.overview', compact('metrics', 'brands', 'platforms', 'recentPublished'));
    }

    public function brand(Request $request): View
    {
        $brands = Brand::withCount([
            'socialPosts as published_count' => fn ($q) => $q->where('status', SocialPost::STATUS_PUBLISHED),
        ])->get();

        $selectedBrandId = $request->integer('brand_id') ?: $this->brandContext->id() ?: $brands->first()?->id;
        $selectedBrand = $selectedBrandId ? Brand::find($selectedBrandId) : null;

        $metrics = $selectedBrandId
            ? $this->analyticsService->getOverviewMetrics(brandId: $selectedBrandId)
            : null;

        return view('admin.analytics.brand', compact('brands', 'selectedBrand', 'metrics'));
    }

    public function platform(Request $request): View
    {
        $platforms = SocialPlatform::withCount([
            'socialPosts as published_count' => fn ($q) => $q->where('status', SocialPost::STATUS_PUBLISHED),
        ])->get();

        $selectedPlatformId = $request->integer('platform_id') ?: $platforms->first()?->id;
        $selectedPlatform = $selectedPlatformId ? SocialPlatform::find($selectedPlatformId) : null;

        $metrics = $selectedPlatformId
            ? $this->analyticsService->getOverviewMetrics(
                brandId: $this->brandContext->id(),
                platformId: $selectedPlatformId
            )
            : null;

        return view('admin.analytics.platform', compact('platforms', 'selectedPlatform', 'metrics'));
    }

    public function content(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'content', 'analytics']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->where('status', SocialPost::STATUS_PUBLISHED)
            ->latest('published_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.analytics.content', compact('posts'));
    }
}
