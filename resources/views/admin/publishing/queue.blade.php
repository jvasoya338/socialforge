@extends('layouts.admin')

@section('title', 'Publishing Queue')
@section('page_title', 'Publishing Queue')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.scheduled') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Queue</li>
@endsection

@section('content')
<div class="card card-outline card-info shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-stream text-info mr-2"></i> Active Queue & Processing Buffer</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Platform</th>
                        <th>Post Content</th>
                        <th>Queue Status</th>
                        <th>Scheduled For</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td><strong>{{ $post->brand->name }}</strong></td>
                            <td><i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}</td>
                            <td class="text-truncate" style="max-width: 350px;">{{ $post->body }}</td>
                            <td><span class="badge {{ $post->statusBadgeClass() }}">{{ ucfirst($post->status) }}</span></td>
                            <td><small class="text-muted">{{ $post->scheduledInBrandTimezone() ?? 'Immediate' }}</small></td>
                            <td class="text-right text-nowrap">
                                @can('publish', $post)
                                    <form action="{{ route('admin.publishing.posts.publish-now', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-success">Dispatch</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Publishing queue is currently empty.
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
