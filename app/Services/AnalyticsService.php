<?php

namespace App\Services;

use App\Models\PostAnalytic;
use App\Models\SocialPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class AnalyticsService
{
    public function syncRecentPublishedPosts(): int
    {
        $posts = SocialPost::with(['socialAccount.credential', 'socialPlatform'])
            ->where('status', SocialPost::STATUS_PUBLISHED)
            ->whereNotNull('platform_post_id')
            ->where('published_at', '>=', now()->subDays(30))
            ->get();

        $synced = 0;
        foreach ($posts as $post) {
            if ($this->fetchPostAnalytics($post)) {
                $synced++;
            }
        }

        return $synced;
    }

    public function fetchPostAnalytics(SocialPost $post): bool
    {
        $post->loadMissing(['socialAccount.credential', 'socialPlatform']);
        $account = $post->socialAccount;

        if (! $account || ! $account->isConnected() || empty($post->platform_post_id)) {
            return false;
        }

        $credential = $account->credential;
        $slug = $post->socialPlatform?->slug;

        try {
            if ($slug === 'facebook') {
                $response = Http::timeout(10)
                    ->withToken($credential->access_token)
                    ->get("https://graph.facebook.com/v20.0/{$post->platform_post_id}/insights", [
                        'metric' => 'post_impressions,post_engaged_users,post_clicks',
                    ]);

                if (! $response->successful()) {
                    return false;
                }

                $raw = $response->json('data', []);
                PostAnalytic::query()->updateOrCreate(
                    [
                        'social_post_id' => $post->id,
                        'metric_date' => now()->toDateString(),
                    ],
                    [
                        'impressions' => (int) data_get($raw, '0.values.0.value', 0),
                        'engagement' => (int) data_get($raw, '1.values.0.value', 0),
                        'clicks' => (int) data_get($raw, '2.values.0.value', 0),
                        'raw_metrics' => $raw,
                    ]
                );

                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Get aggregated analytics summary without fabricating unavailable data.
     */
    public function getOverviewMetrics(?int $brandId = null, ?int $platformId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : now()->endOfDay();

        $postsQuery = SocialPost::query()
            ->where('status', SocialPost::STATUS_PUBLISHED)
            ->whereBetween('published_at', [$from, $to]);

        if ($brandId) {
            $postsQuery->where('brand_id', $brandId);
        }

        if ($platformId) {
            $postsQuery->where('social_platform_id', $platformId);
        }

        $publishedCount = (clone $postsQuery)->count();

        $analyticsQuery = PostAnalytic::query()
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('socialPost', function ($q) use ($brandId, $platformId) {
                if ($brandId) {
                    $q->where('brand_id', $brandId);
                }
                if ($platformId) {
                    $q->where('social_platform_id', $platformId);
                }
            });

        $hasData = (clone $analyticsQuery)->exists();

        return [
            'has_metric_data' => $hasData,
            'posts_published' => $publishedCount,
            'impressions' => $hasData ? (int) (clone $analyticsQuery)->sum('impressions') : null,
            'reach' => $hasData ? (int) (clone $analyticsQuery)->sum('reach') : null,
            'views' => $hasData ? (int) (clone $analyticsQuery)->sum('views') : null,
            'likes' => $hasData ? (int) (clone $analyticsQuery)->sum('likes') : null,
            'comments' => $hasData ? (int) (clone $analyticsQuery)->sum('comments') : null,
            'shares' => $hasData ? (int) (clone $analyticsQuery)->sum('shares') : null,
            'clicks' => $hasData ? (int) (clone $analyticsQuery)->sum('clicks') : null,
            'engagement' => $hasData ? (int) (clone $analyticsQuery)->sum('engagement') : null,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
        ];
    }
}
