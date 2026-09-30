@extends('layouts.admin')

@section('title', $content->title)
@section('page_title', 'Content Details')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.content.index') }}">Content</a></li>
    <li class="breadcrumb-item active">{{ Str::limit($content->title, 25) }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Left Column: Source Content -->
    <div class="col-md-7">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <span class="badge badge-light border text-uppercase mr-2">{{ $content->type }}</span>
                <span class="badge badge-info">{{ $content->brand->name }}</span>
                <div class="card-tools">
                    <a href="{{ route('admin.ai.generator', ['content_id' => $content->id]) }}" class="btn btn-sm btn-purple">
                        <i class="fas fa-magic mr-1"></i> Generate Social Posts
                    </a>
                </div>
            </div>
            <div class="card-body">
                <h3 class="font-weight-bold mb-3">{{ $content->title }}</h3>

                @if($content->featured_image)
                    <div class="mb-3 text-center">
                        <img src="{{ $content->featured_image }}" alt="{{ $content->title }}" class="img-fluid rounded border" style="max-height: 250px;">
                    </div>
                @endif

                @if($content->summary)
                    <div class="callout callout-info mb-3">
                        <h6 class="font-weight-bold">Summary / Facts</h6>
                        <p class="mb-0">{{ $content->summary }}</p>
                    </div>
                @endif

                @if($content->body)
                    <h6 class="font-weight-bold text-muted text-uppercase text-xs mt-3">Full Body</h6>
                    <div class="p-3 bg-light rounded border text-break-word">
                        {!! nl2br(e($content->body)) !!}
                    </div>
                @endif

                <hr>

                <dl class="row mb-0 text-sm">
                    <dt class="col-sm-3 text-muted">External ID</dt>
                    <dd class="col-sm-9"><code>{{ $content->external_id ?: 'None' }}</code></dd>

                    <dt class="col-sm-3 text-muted">Source URL</dt>
                    <dd class="col-sm-9">
                        @if($content->source_url)
                            <a href="{{ $content->source_url }}" target="_blank" rel="noopener">{{ $content->source_url }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3 text-muted">Ingested At</dt>
                    <dd class="col-sm-9">{{ $content->created_at->format('Y-m-d H:i:s T') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Right Column: Associated Social Posts & Media -->
    <div class="col-md-5">
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-share-alt mr-2"></i> Generated Social Posts ({{ $content->socialPosts->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($content->socialPosts as $post)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>
                                    <i class="{{ $post->socialPlatform->icon }} mr-1"></i>
                                    <strong>{{ $post->socialPlatform->name }}</strong>
                                </span>
                                <span class="badge {{ $post->statusBadgeClass() }}">{{ ucfirst($post->status) }}</span>
                            </div>
                            <p class="text-sm mb-2 text-truncate">{{ $post->body }}</p>
                            <small class="text-muted">Approval: <strong>{{ ucfirst($post->approval_status) }}</strong></small>
                        </li>
                    @empty
                        <li class="list-group-item text-center py-4 text-muted">
                            No social posts generated yet for this content item.
                            <div class="mt-2">
                                <a href="{{ route('admin.ai.generator', ['content_id' => $content->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-magic mr-1"></i> Generate Now
                                </a>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Attached Media -->
        @if($content->contentMedia->isNotEmpty())
            <div class="card card-outline card-info shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-photo-video mr-2"></i> Attached Media</h3>
                </div>
                <div class="card-body p-2">
                    <div class="row">
                        @foreach($content->contentMedia as $m)
                            <div class="col-4 mb-2 text-center">
                                @if($m->type === 'video')
                                    <div class="bg-dark p-3 rounded text-white text-xs">
                                        <i class="fas fa-video fa-2x d-block mb-1"></i> Video
                                    </div>
                                @else
                                    <img src="{{ $m->publicUrl() }}" class="img-thumbnail" style="height: 70px; object-fit: cover;">
                                @endif
                                <small class="text-muted d-block text-truncate">{{ $m->original_name ?: $m->type }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
