@extends('layouts.admin')

@section('title', 'Analytics Overview')
@section('page_title', 'Analytics Overview')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Analytics Overview</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold"><i class="fas fa-chart-line text-primary mr-2"></i> Performance Summary</h3>
        <div class="card-tools">
            <form method="GET" action="{{ route('admin.analytics.overview') }}" class="form-inline">
                <input type="date" name="from_date" class="form-control form-control-sm mr-2" value="{{ $metrics['from_date'] }}">
                <input type="date" name="to_date" class="form-control form-control-sm mr-2" value="{{ $metrics['to_date'] }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-filter mr-1"></i> Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-6 mb-3 text-center">
                <span class="text-muted text-xs text-uppercase d-block">Published Posts</span>
                <h3 class="font-weight-bold mb-0 text-primary">{{ number_format($metrics['posts_published']) }}</h3>
            </div>
            <div class="col-md-3 col-6 mb-3 text-center">
                <span class="text-muted text-xs text-uppercase d-block">Total Impressions</span>
                <h3 class="font-weight-bold mb-0">{{ $metrics['impressions'] !== null ? number_format($metrics['impressions']) : '—' }}</h3>
                @if($metrics['impressions'] === null) <small class="text-muted">Awaiting platform data</small> @endif
            </div>
            <div class="col-md-3 col-6 mb-3 text-center">
                <span class="text-muted text-xs text-uppercase d-block">Total Reach</span>
                <h3 class="font-weight-bold mb-0">{{ $metrics['reach'] !== null ? number_format($metrics['reach']) : '—' }}</h3>
                @if($metrics['reach'] === null) <small class="text-muted">Awaiting platform data</small> @endif
            </div>
            <div class="col-md-3 col-6 mb-3 text-center">
                <span class="text-muted text-xs text-uppercase d-block">Total Engagement</span>
                <h3 class="font-weight-bold mb-0">{{ $metrics['engagement'] !== null ? number_format($metrics['engagement']) : '—' }}</h3>
                @if($metrics['engagement'] === null) <small class="text-muted">Awaiting platform data</small> @endif
            </div>
        </div>

        @if(!$metrics['has_metric_data'])
            <div class="alert alert-light border text-center py-4 mt-3">
                <i class="fas fa-info-circle text-info fa-2x mb-2 d-block"></i>
                <strong>No external API metrics synchronized yet.</strong><br>
                <small class="text-muted">Once social accounts are connected and posts are published via official APIs, real platform metrics (impressions, clicks, reactions) will appear here. SocialForge never fabricates fake numbers.</small>
            </div>
        @endif
    </div>
</div>

<!-- Published Posts Table -->
<div class="card card-outline card-secondary shadow-sm mb-4">
    <div class="card-header border-0">
        <h3 class="card-title font-weight-bold">Recent Published Posts & Delivery</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Platform</th>
                        <th>Post Excerpt</th>
                        <th>Published Time</th>
                        <th>Live Link</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPublished as $post)
                        <tr>
                            <td><strong>{{ $post->brand->name }}</strong></td>
                            <td><i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}</td>
                            <td class="text-truncate" style="max-width: 380px;">{{ $post->body }}</td>
                            <td><small class="text-muted">{{ $post->published_at?->diffForHumans() }}</small></td>
                            <td>
                                @if($post->platform_post_url)
                                    <a href="{{ $post->platform_post_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-external-link-alt mr-1"></i> View
                                    </a>
                                @else
                                    <code class="text-xs">{{ $post->platform_post_id }}</code>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No published posts in selected window.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
