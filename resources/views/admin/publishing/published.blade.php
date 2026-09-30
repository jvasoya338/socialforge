@extends('layouts.admin')

@section('title', 'Published Posts')
@section('page_title', 'Published Posts')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.drafts') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Published</li>
@endsection

@section('content')
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-check-circle text-success mr-2"></i> Successfully Published Posts</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Platform & Account</th>
                        <th>Post Copy</th>
                        <th>Platform Link</th>
                        <th>Published At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td><strong>{{ $post->brand->name }}</strong></td>
                            <td>
                                <i class="{{ $post->socialPlatform->icon }} mr-1"></i>
                                {{ $post->socialPlatform->name }}
                                <small class="text-muted d-block">{{ $post->socialAccount?->account_name }}</small>
                            </td>
                            <td>
                                <p class="mb-1 text-sm text-truncate" style="max-width: 400px;">{{ $post->body }}</p>
                                @if(!empty($post->hashtags))
                                    <small class="text-primary font-weight-bold">{{ implode(' ', array_map(fn($t) => '#'.ltrim($t, '#'), $post->hashtags)) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($post->platform_post_url)
                                    <a href="{{ $post->platform_post_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-external-link-alt mr-1"></i> View Live
                                    </a>
                                @else
                                    <code class="text-xs">{{ $post->platform_post_id ?: 'published' }}</code>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $post->published_at?->diffForHumans() ?? '—' }}</small></td>
                            <td class="text-right text-nowrap">
                                <form action="{{ route('admin.publishing.posts.duplicate', $post) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-secondary" title="Duplicate as new draft">
                                        <i class="fas fa-clone mr-1"></i> Duplicate
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-paper-plane fa-2x mb-2 d-block"></i>
                                No published posts yet.
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
