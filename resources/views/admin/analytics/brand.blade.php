@extends('layouts.admin')

@section('title', 'Brand Analytics')
@section('page_title', 'Brand Analytics Breakdown')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.analytics.overview') }}">Analytics</a></li>
    <li class="breadcrumb-item active">Brand Analytics</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">Brand Performance Selection</h3>
        <div class="card-tools">
            <form method="GET" action="{{ route('admin.analytics.brand') }}" class="form-inline">
                <select name="brand_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" {{ $selectedBrand && $selectedBrand->id === $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->published_count }} published)
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="card-body">
        @if($selectedBrand)
            <div class="row text-center mb-4">
                <div class="col-md-3 col-6 mb-2">
                    <span class="text-muted text-xs text-uppercase d-block">Published Posts</span>
                    <h3 class="font-weight-bold mb-0 text-primary">{{ $metrics['posts_published'] ?? 0 }}</h3>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <span class="text-muted text-xs text-uppercase d-block">Impressions</span>
                    <h3 class="font-weight-bold mb-0">{{ $metrics['impressions'] !== null ? number_format($metrics['impressions']) : '—' }}</h3>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <span class="text-muted text-xs text-uppercase d-block">Reach</span>
                    <h3 class="font-weight-bold mb-0">{{ $metrics['reach'] !== null ? number_format($metrics['reach']) : '—' }}</h3>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <span class="text-muted text-xs text-uppercase d-block">Engagement</span>
                    <h3 class="font-weight-bold mb-0">{{ $metrics['engagement'] !== null ? number_format($metrics['engagement']) : '—' }}</h3>
                </div>
            </div>

            @if(!$metrics['has_metric_data'])
                <div class="alert alert-light border text-center py-3">
                    <small class="text-muted">Metrics for <strong>{{ $selectedBrand->name }}</strong> will populate as connected accounts generate social impressions via official APIs.</small>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
