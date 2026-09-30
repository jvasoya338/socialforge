@extends('layouts.admin')

@section('title', 'Media Library')
@section('page_title', 'Media Library')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.content.index') }}">Content</a></li>
    <li class="breadcrumb-item active">Media Library</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Brand Assets & Uploaded Media</h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.content.media') }}" class="form-inline mr-2">
                <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Media Types</option>
                    <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Images</option>
                    <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Videos</option>
                    <option value="gif" {{ request('type') === 'gif' ? 'selected' : '' }}>GIFs</option>
                </select>
            </form>
            @can('edit-content')
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#uploadMediaModal">
                    <i class="fas fa-upload mr-1"></i> Upload Asset
                </button>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($mediaItems as $media)
                <div class="col-md-2 col-sm-4 col-6 mb-4">
                    <div class="card h-100 border shadow-sm">
                        <div class="text-center p-2 bg-light d-flex align-items-center justify-content-center" style="height: 140px; overflow: hidden;">
                            @if($media->type === 'video')
                                <div class="text-center">
                                    <i class="fas fa-video fa-3x text-secondary mb-1"></i>
                                    <span class="badge badge-dark d-block">Video</span>
                                </div>
                            @else
                                <img src="{{ $media->publicUrl() }}" class="img-fluid rounded" style="max-height: 120px;" alt="{{ $media->original_name }}">
                            @endif
                        </div>
                        <div class="card-body p-2">
                            <strong class="d-block text-xs text-truncate" title="{{ $media->original_name }}">{{ $media->original_name ?: basename($media->path) }}</strong>
                            <small class="text-muted d-block">{{ $media->brand?->name ?? 'Shared' }}</small>
                            <small class="text-muted d-block">{{ number_format(($media->size ?? 0) / 1024, 1) }} KB</small>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-photo-video fa-3x mb-3 d-block"></i>
                    No media items in library. Upload images or videos to attach to social posts.
                </div>
            @endforelse
        </div>
    </div>
    @if($mediaItems->hasPages())
        <div class="card-footer bg-light">
            {{ $mediaItems->links() }}
        </div>
    @endif
</div>

<!-- Modal: Upload Media -->
<div class="modal fade" id="uploadMediaModal" tabindex="-1" role="dialog" aria-labelledby="uploadMediaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.content.media.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="uploadMediaModalLabel">Upload Media Asset</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="media_brand_id">Target Brand</label>
                        <select name="brand_id" id="media_brand_id" class="form-control">
                            <option value="">Shared / No Brand</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (isset($currentBrand) && $currentBrand?->id === $b->id) ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="media_file">Select File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="media_file" class="form-control-file" required>
                        <small class="form-text text-muted">Supported: JPEG, PNG, WEBP, GIF, MP4, QuickTime, WebM (up to 20MB). Strict MIME validation applied.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
