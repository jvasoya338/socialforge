@extends('layouts.admin')

@section('title', 'Scheduled Posts')
@section('page_title', 'Scheduled Posts')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.drafts') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Scheduled</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Upcoming Scheduled Social Posts</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Platform & Account</th>
                        <th>Post Copy</th>
                        <th>Scheduled Time (Brand Timezone)</th>
                        <th>Scheduled (UTC)</th>
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
                                <small class="text-muted d-block">{{ $post->socialAccount?->account_name ?? 'Auto' }}</small>
                            </td>
                            <td class="text-truncate" style="max-width: 350px;">{{ $post->body }}</td>
                            <td><span class="badge badge-light border">{{ $post->scheduledInBrandTimezone() }}</span></td>
                            <td><small class="text-muted">{{ $post->scheduled_at?->format('Y-m-d H:i') }} UTC</small></td>
                            <td class="text-right text-nowrap">
                                @can('publish', $post)
                                    <form action="{{ route('admin.publishing.posts.publish-now', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-success">
                                            <i class="fas fa-paper-plane mr-1"></i> Publish Now
                                        </button>
                                    </form>
                                @endcan
                                <form action="{{ route('admin.publishing.posts.cancel', $post) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-danger">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No scheduled posts pending execution.
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
