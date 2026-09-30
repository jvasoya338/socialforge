@extends('layouts.admin')

@section('title', 'Failed Posts')
@section('page_title', 'Failed Publishing Attempts')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.drafts') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Failed Posts</li>
@endsection

@section('content')
<div class="card card-outline card-danger shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-exclamation-triangle text-danger mr-2"></i> Failed Posts & Action Center</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand & Platform</th>
                        <th>Account</th>
                        <th>Post Copy</th>
                        <th>Attempt Count</th>
                        <th>Failure Category</th>
                        <th>Error Message</th>
                        <th>Last Attempt</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        @php $lastAttempt = $post->publishingAttempts->first(); @endphp
                        <tr>
                            <td>
                                <strong>{{ $post->brand->name }}</strong>
                                <div class="mt-1">
                                    <span class="badge badge-light border">
                                        <i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($post->socialAccount)
                                    <span class="badge {{ $post->socialAccount->healthBadgeClass() }}">{{ $post->socialAccount->account_name }}</span>
                                @else
                                    <span class="badge badge-secondary">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 250px;">{{ $post->body }}</td>
                            <td>
                                <span class="badge badge-dark">
                                    {{ $post->publishingAttempts->count() }} attempt(s)
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-danger text-uppercase">
                                    {{ $post->failure_category ?: ($lastAttempt?->failure_category ?? 'unknown') }}
                                </span>
                            </td>
                            <td style="max-width: 280px;">
                                <small class="text-danger font-weight-bold d-block text-break-word">
                                    {{ $post->last_error_message ?: ($lastAttempt?->error_message ?? 'Unknown error occurred.') }}
                                </small>
                            </td>
                            <td><small class="text-muted">{{ $lastAttempt?->created_at?->diffForHumans() ?? $post->updated_at->diffForHumans() }}</small></td>
                            <td class="text-right text-nowrap">
                                @can('publish', $post)
                                    <form action="{{ route('admin.publishing.posts.retry', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Retry Publishing">
                                            <i class="fas fa-redo-alt mr-1"></i> Retry
                                        </button>
                                    </form>
                                @endcan

                                @if($post->failure_category === 'authentication' && $post->socialAccount)
                                    <a href="{{ route('admin.social-accounts.oauth.initiate', $post->socialAccount) }}" class="btn btn-xs btn-warning" title="Reconnect Account">
                                        <i class="fas fa-plug mr-1"></i> Reconnect
                                    </a>
                                @endif

                                <form action="{{ route('admin.publishing.posts.cancel', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this post?');">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-secondary" title="Cancel Post">
                                        <i class="fas fa-ban"></i> Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-success">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                No failed posts! All publishing operations are healthy.
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
