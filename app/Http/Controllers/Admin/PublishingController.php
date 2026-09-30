<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSocialPostRequest;
use App\Jobs\PublishSocialPostJob;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\PublishingAttempt;
use App\Models\SocialPlatform;
use App\Models\SocialPost;
use App\Services\AI\AIContentService;
use App\Services\BrandContext;
use App\Services\Publishing\SocialPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublishingController extends Controller
{
    public function __construct(
        protected BrandContext $brandContext,
        protected SocialPublishingService $publishingService,
        protected AIContentService $aiContentService,
    ) {}

    public function drafts(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount', 'creator']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->where('status', SocialPost::STATUS_DRAFT)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $brands = Brand::orderBy('name')->get();
        $platforms = SocialPlatform::active()->get();

        return view('admin.publishing.drafts', compact('posts', 'brands', 'platforms'));
    }

    public function approvalQueue(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount', 'content']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->where('status', SocialPost::STATUS_PENDING_APPROVAL)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.publishing.approval-queue', compact('posts'));
    }

    public function calendar(Request $request): View
    {
        $brandId = $request->integer('brand_id') ?: $this->brandContext->id();
        $brand = $brandId ? Brand::find($brandId) : null;
        $tz = $brand?->timezone ?? 'UTC';

        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month.'-01', $tz)->startOfMonth()->utc();
        $endOfMonth = Carbon::parse($month.'-01', $tz)->endOfMonth()->utc();

        $scheduledPosts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount']),
            'brand_id',
            $brandId
        )->whereIn('status', [SocialPost::STATUS_SCHEDULED, SocialPost::STATUS_PUBLISHED])
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->orderBy('scheduled_at')
            ->get();

        $brands = Brand::orderBy('name')->get();

        return view('admin.publishing.calendar', compact('scheduledPosts', 'brands', 'month', 'tz'));
    }

    public function scheduled(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->where('status', SocialPost::STATUS_SCHEDULED)
            ->orderBy('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.publishing.scheduled', compact('posts'));
    }

    public function queue(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->whereIn('status', [SocialPost::STATUS_PROCESSING, SocialPost::STATUS_SCHEDULED])
            ->orderBy('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.publishing.queue', compact('posts'));
    }

    public function published(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount']),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->where('status', SocialPost::STATUS_PUBLISHED)
            ->latest('published_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.publishing.published', compact('posts'));
    }

    public function failed(Request $request): View
    {
        $posts = $this->brandContext->apply(
            SocialPost::with(['brand', 'socialPlatform', 'socialAccount', 'publishingAttempts' => fn ($q) => $q->latest()->limit(3)]),
            'brand_id',
            $request->integer('brand_id') ?: null
        )->where('status', SocialPost::STATUS_FAILED)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.publishing.failed', compact('posts'));
    }

    public function store(StoreSocialPostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $brand = Brand::findOrFail($data['brand_id']);

        $hashtags = ! empty($data['hashtags'])
            ? array_values(array_filter(array_map('trim', explode(',', $data['hashtags']))))
            : null;

        $scheduledAt = ! empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'], $brand->timezone)->utc()
            : null;

        $post = SocialPost::create([
            'brand_id' => $brand->id,
            'content_id' => $data['content_id'] ?? null,
            'social_platform_id' => $data['social_platform_id'],
            'social_account_id' => $data['social_account_id'] ?? null,
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'cta' => $data['cta'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'hashtags' => $hashtags,
            'status' => $scheduledAt ? SocialPost::STATUS_SCHEDULED : SocialPost::STATUS_DRAFT,
            'approval_status' => $brand->requiresApproval() ? SocialPost::APPROVAL_PENDING : SocialPost::APPROVAL_APPROVED,
            'publish_mode' => $scheduledAt ? SocialPost::PUBLISH_MODE_SCHEDULED : SocialPost::PUBLISH_MODE_MANUAL,
            'scheduled_at' => $scheduledAt,
            'idempotency_key' => 'sf_post_'.Str::uuid()->toString(),
            'created_by' => Auth::id(),
        ]);

        ActivityLog::record(
            action: 'post.created',
            description: "Created post #{$post->id} ({$post->status})",
            subject: $post,
            brandId: $post->brand_id,
        );

        return redirect()->route('admin.publishing.drafts')
            ->with('success', "Post #{$post->id} created.");
    }

    public function update(Request $request, SocialPost $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $data = $request->validate([
            'body' => ['required', 'string'],
            'cta' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:1024'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $scheduledAt = ! empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'], $post->brand?->timezone ?? 'UTC')->utc()
            : $post->scheduled_at;

        $post->update([
            'body' => $data['body'],
            'cta' => $data['cta'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'scheduled_at' => $scheduledAt,
        ]);

        ActivityLog::record(
            action: 'post.edited',
            description: "Updated post #{$post->id}",
            subject: $post,
            brandId: $post->brand_id,
        );

        return back()->with('success', "Post #{$post->id} updated.");
    }

    public function approve(SocialPost $post): RedirectResponse
    {
        Gate::authorize('approve', $post);

        $newStatus = $post->scheduled_at ? SocialPost::STATUS_SCHEDULED : SocialPost::STATUS_APPROVED;

        $post->update([
            'status' => $newStatus,
            'approval_status' => SocialPost::APPROVAL_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        ActivityLog::record(
            action: 'post.approved',
            description: "Approved post #{$post->id}",
            subject: $post,
            brandId: $post->brand_id,
        );

        return back()->with('success', "Post #{$post->id} approved.");
    }

    public function reject(Request $request, SocialPost $post): RedirectResponse
    {
        Gate::authorize('approve', $post);

        $reason = $request->input('rejection_reason', 'Rejected by editor review.');

        $post->update([
            'status' => SocialPost::STATUS_DRAFT,
            'approval_status' => SocialPost::APPROVAL_REJECTED,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        ActivityLog::record(
            action: 'post.rejected',
            description: "Rejected post #{$post->id}: {$reason}",
            subject: $post,
            brandId: $post->brand_id,
        );

        return back()->with('success', "Post #{$post->id} rejected back to draft.");
    }

    public function schedule(Request $request, SocialPost $post): RedirectResponse
    {
        Gate::authorize('schedule', $post);

        $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $brandTimezone = $post->brand?->timezone ?? 'UTC';
        $utcScheduled = Carbon::parse($request->input('scheduled_at'), $brandTimezone)->utc();

        $post->update([
            'scheduled_at' => $utcScheduled,
            'status' => SocialPost::STATUS_SCHEDULED,
            'publish_mode' => SocialPost::PUBLISH_MODE_SCHEDULED,
        ]);

        ActivityLog::record(
            action: 'post.scheduled',
            description: "Scheduled post #{$post->id} for {$post->scheduledInBrandTimezone()}",
            subject: $post,
            brandId: $post->brand_id,
        );

        return back()->with('success', "Post #{$post->id} scheduled for {$post->scheduledInBrandTimezone()}.");
    }

    public function publishNow(SocialPost $post): RedirectResponse
    {
        Gate::authorize('publish', $post);

        $attempt = $this->publishingService->publishPost($post, ignoreScheduleTime: true);

        if ($attempt->status === PublishingAttempt::STATUS_SUCCESS) {
            return back()->with('success', "Post #{$post->id} published successfully to {$post->socialPlatform->name}!");
        }

        return back()->with('error', "Publishing failed ({$attempt->failure_category}): {$attempt->error_message}");
    }

    public function retry(SocialPost $post): RedirectResponse
    {
        Gate::authorize('publish', $post);

        PublishSocialPostJob::dispatch($post->id, true);

        return back()->with('success', "Dispatched retry job for post #{$post->id}.");
    }

    public function duplicate(SocialPost $post): RedirectResponse
    {
        Gate::authorize('create', SocialPost::class);

        $replica = $post->replicate([
            'platform_post_id',
            'platform_post_url',
            'published_at',
            'approved_at',
            'approved_by',
            'rejected_at',
            'rejected_by',
            'last_error_message',
            'failure_category',
        ]);

        $replica->status = SocialPost::STATUS_DRAFT;
        $replica->approval_status = SocialPost::APPROVAL_PENDING;
        $replica->idempotency_key = 'sf_post_'.Str::uuid()->toString();
        $replica->created_by = Auth::id();
        $replica->save();

        foreach ($post->media as $mediaItem) {
            $mediaReplica = $mediaItem->replicate();
            $mediaReplica->social_post_id = $replica->id;
            $mediaReplica->save();
        }

        ActivityLog::record(
            action: 'post.duplicated',
            description: "Duplicated post #{$post->id} as new draft #{$replica->id}",
            subject: $replica,
            brandId: $replica->brand_id,
        );

        return back()->with('success', "Duplicated as new draft #{$replica->id}.");
    }

    public function cancel(SocialPost $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $post->update(['status' => SocialPost::STATUS_CANCELLED]);

        ActivityLog::record(
            action: 'post.cancelled',
            description: "Cancelled post #{$post->id}",
            subject: $post,
            brandId: $post->brand_id,
        );

        return back()->with('success', "Post #{$post->id} cancelled.");
    }

    public function humanizePost(SocialPost $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $this->aiContentService->humanizeExistingPost($post);

        return back()->with('success', "Post #{$post->id} refined through the Brand Voice Humanization pass.");
    }
}
