@extends('layouts.admin')

@section('title', 'Approval Queue')
@section('page_title', 'Editorial Approval Queue')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.drafts') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Approval Queue</li>
@endsection

@section('content')
<div class="card card-outline card-warning shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-check-double text-warning mr-2"></i> Posts Awaiting Review & Approval
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand & Platform</th>
                        <th>Generated Social Copy</th>
                        <th>Target Account</th>
                        <th>Associated Content</th>
                        <th>Schedule</th>
                        <th class="text-right">Editorial Review Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td>
                                <strong>{{ $post->brand->name }}</strong>
                                <div class="mt-1">
                                    <span class="badge badge-light border">
                                        <i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}
                                    </span>
                                </div>
                            </td>
                            <td style="max-width: 400px;">
                                <p class="mb-1 text-sm">{{ $post->body }}</p>
                                @if(!empty($post->cta))
                                    <small class="text-info font-weight-bold d-block"><i class="fas fa-bullhorn mr-1"></i> {{ $post->cta }}</small>
                                @endif
                                @if(!empty($post->hashtags))
                                    <small class="text-primary font-weight-bold d-block">{{ implode(' ', array_map(fn($t) => '#'.ltrim($t, '#'), $post->hashtags)) }}</small>
                                @endif
                                @if($post->link_url)
                                    <small class="text-muted d-block text-truncate"><i class="fas fa-link mr-1"></i> {{ $post->link_url }}</small>
                                @endif
                            </td>
                            <td>
                                @if($post->socialAccount)
                                    <span class="badge {{ $post->socialAccount->healthBadgeClass() }}">{{ $post->socialAccount->account_name }}</span>
                                @else
                                    <span class="badge badge-secondary">Not Assigned</span>
                                @endif
                            </td>
                            <td>
                                @if($post->content)
                                    <a href="{{ route('admin.content.show', $post->content) }}" class="text-sm font-weight-bold text-dark text-truncate d-block" style="max-width: 180px;">
                                        {{ $post->content->title }}
                                    </a>
                                    <small class="text-muted text-uppercase">{{ $post->content->type }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($post->scheduled_at)
                                    <small class="badge badge-light border">{{ $post->scheduledInBrandTimezone() }}</small>
                                @else
                                    <small class="text-muted">Publish upon approval</small>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                <form action="{{ route('admin.publishing.posts.humanize', $post) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-purple" title="Humanize Again">
                                        <i class="fas fa-magic"></i> Humanize
                                    </button>
                                </form>

                                @can('approve', $post)
                                    <form action="{{ route('admin.publishing.posts.approve', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-success" title="Approve Post">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.publishing.posts.reject', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this post back to drafts?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Reject Post">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                @endcan

                                @can('publish', $post)
                                    <form action="{{ route('admin.publishing.posts.publish-now', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Publish this post immediately to social media?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-primary" title="Publish Now">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2 text-success d-block"></i>
                                Approval queue is clear! No posts pending review.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($posts->hasPages())
        <div class="card-footer bg-light">
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection
