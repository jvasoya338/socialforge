@extends('layouts.admin')

@section('title', 'Content Analytics')
@section('page_title', 'Content Analytics')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.analytics.overview') }}">Analytics</a></li>
    <li class="breadcrumb-item active">Content Analytics</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Published Post Metrics</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand & Platform</th>
                        <th>Post Excerpt</th>
                        <th>Source Content</th>
                        <th>Published Date</th>
                        <th>Impressions</th>
                        <th>Engagement</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        @php $analytic = $post->analytics->first(); @endphp
                        <tr>
                            <td>
                                <strong>{{ $post->brand->name }}</strong>
                                <small class="d-block text-muted">
                                    <i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}
                                </small>
                            </td>
                            <td class="text-truncate" style="max-width: 300px;">{{ $post->body }}</td>
                            <td>
                                @if($post->content)
                                    <span class="text-truncate d-block" style="max-width: 180px;">{{ $post->content->title }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $post->published_at?->format('Y-m-d') ?? '—' }}</small></td>
                            <td>{{ $analytic?->impressions !== null ? number_format($analytic->impressions) : '—' }}</td>
                            <td>{{ $analytic?->engagement !== null ? number_format($analytic->engagement) : '—' }}</td>
                            <td>{{ $analytic?->clicks !== null ? number_format($analytic->clicks) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No published posts found for content analytics.
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
