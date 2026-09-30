@extends('layouts.admin')

@section('title', 'Draft Posts')
@section('page_title', 'Draft Posts')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.drafts') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Drafts</li>
@endsection

@section('content')
<div class="card card-outline card-secondary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Draft Social Posts</h3>
        <div class="card-tools d-flex">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createPostModal">
                <i class="fas fa-plus-circle mr-1"></i> Compose Post
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Platform</th>
                        <th>Post Copy & Hashtags</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td><strong>{{ $post->brand->name }}</strong></td>
                            <td><i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}</td>
                            <td>
                                <p class="mb-1 text-sm">{{ $post->body }}</p>
                                @if(!empty($post->hashtags))
                                    <small class="text-primary font-weight-bold d-block">{{ implode(' ', array_map(fn($t) => '#'.ltrim($t, '#'), $post->hashtags)) }}</small>
                                @endif
                                @if($post->link_url)
                                    <small class="text-muted d-block text-truncate" style="max-width: 300px;"><i class="fas fa-link mr-1"></i> {{ $post->link_url }}</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $post->creator?->name ?? 'System' }}</small></td>
                            <td><small class="text-muted">{{ $post->created_at->diffForHumans() }}</small></td>
                            <td class="text-right text-nowrap">
                                <form action="{{ route('admin.publishing.posts.humanize', $post) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-purple" title="Apply Humanization Pass">
                                        <i class="fas fa-magic"></i> Humanize
                                    </button>
                                </form>
                                @can('approve', $post)
                                    <form action="{{ route('admin.publishing.posts.approve', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-success" title="Approve Draft">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                @endcan
                                <form action="{{ route('admin.publishing.posts.duplicate', $post) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-secondary" title="Duplicate">
                                        <i class="fas fa-clone"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
                                No draft posts found. Compose a new post or generate drafts via the AI Generator.
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

<!-- Modal: Compose Post -->
<div class="modal fade" id="createPostModal" tabindex="-1" role="dialog" aria-labelledby="createPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('admin.publishing.posts.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="createPostModalLabel">Compose Social Post</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="post_brand_id">Brand <span class="text-danger">*</span></label>
                            <select name="brand_id" id="post_brand_id" class="form-control" required>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}" {{ (isset($currentBrand) && $currentBrand?->id === $b->id) ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="post_platform_id">Social Platform <span class="text-danger">*</span></label>
                            <select name="social_platform_id" id="post_platform_id" class="form-control" required>
                                @foreach($platforms as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="post_body">Post Content <span class="text-danger">*</span></label>
                        <textarea name="body" id="post_body" rows="4" class="form-control" placeholder="Write post copy..." required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="post_cta">Call to Action (Optional)</label>
                            <input type="text" name="cta" id="post_cta" class="form-control" placeholder="e.g. Read the full story, Play now in your browser">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="post_link">Link URL (Optional)</label>
                            <input type="url" name="link_url" id="post_link" class="form-control" placeholder="https://example.com/link">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="post_hashtags">Hashtags (Optional)</label>
                            <input type="text" name="hashtags" id="post_hashtags" class="form-control" placeholder="#Brand, #Topic">
                            <small class="text-muted">Comma-separated.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="post_scheduled">Schedule Time (Optional)</label>
                            <input type="datetime-local" name="scheduled_at" id="post_scheduled" class="form-control">
                            <small class="text-muted">Leave empty to save as Draft.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
