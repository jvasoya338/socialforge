@extends('layouts.admin')

@section('title', 'Content Library')
@section('page_title', 'Content Library')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Content Library</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Content Items</h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.content.index') }}" class="form-inline mr-2">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search title or ID..." value="{{ request('search') }}">
                <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="game" {{ request('type') === 'game' ? 'selected' : '' }}>Game</option>
                    <option value="article" {{ request('type') === 'article' ? 'selected' : '' }}>Article</option>
                    <option value="product" {{ request('type') === 'product' ? 'selected' : '' }}>Product</option>
                    <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Video</option>
                    <option value="announcement" {{ request('type') === 'announcement' ? 'selected' : '' }}>Announcement</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
            </form>
            @can('edit-content')
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createContentModal">
                    <i class="fas fa-plus-circle mr-1"></i> Add Manual Content
                </button>
            @endcan
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Title & External ID</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Social Posts</th>
                        <th>Published Date</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contents as $item)
                        <tr>
                            <td><strong>{{ $item->brand->name }}</strong></td>
                            <td>
                                <a href="{{ route('admin.content.show', $item) }}" class="font-weight-bold text-dark">
                                    {{ $item->title }}
                                </a>
                                @if($item->external_id)
                                    <small class="text-muted d-block font-monospace">id: {{ $item->external_id }}</small>
                                @endif
                            </td>
                            <td><span class="badge badge-light border text-uppercase">{{ $item->type }}</span></td>
                            <td><small class="text-muted">{{ $item->contentSource?->name ?? 'Manual / Direct' }}</small></td>
                            <td>
                                <span class="badge badge-info">{{ $item->social_posts_count }} post(s)</span>
                            </td>
                            <td><small class="text-muted">{{ $item->published_at?->format('M d, Y') ?? '—' }}</small></td>
                            <td>
                                <span class="badge badge-secondary">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.content.show', $item) }}" class="btn btn-xs btn-outline-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.ai.generator', ['content_id' => $item->id]) }}" class="btn btn-xs btn-outline-purple" title="Generate Social Posts">
                                    <i class="fas fa-magic"></i> Generate
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-newspaper fa-2x mb-2 d-block"></i>
                                No content found in library. Add manual content or push via the API.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($contents->hasPages())
        <div class="card-footer bg-light">
            {{ $contents->links() }}
        </div>
    @endif
</div>

<!-- Modal: Add Manual Content -->
<div class="modal fade" id="createContentModal" tabindex="-1" role="dialog" aria-labelledby="createContentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('admin.content.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="createContentModalLabel">Add Content to Library</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_brand_id">Brand <span class="text-danger">*</span></label>
                            <select name="brand_id" id="modal_brand_id" class="form-control" required>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}" {{ (isset($currentBrand) && $currentBrand?->id === $b->id) ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="modal_type">Content Type <span class="text-danger">*</span></label>
                            <select name="type" id="modal_type" class="form-control" required>
                                <option value="article">Article / News</option>
                                <option value="game">Game Release / Arcade</option>
                                <option value="product">Product / E-Commerce</option>
                                <option value="video">Video</option>
                                <option value="announcement">Announcement</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="modal_title">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="modal_title" class="form-control" placeholder="Headline, game title, or product name" required>
                    </div>

                    <div class="form-group">
                        <label for="modal_external_id">External ID (Optional)</label>
                        <input type="text" name="external_id" id="modal_external_id" class="form-control" placeholder="e.g. article_982, game_402">
                    </div>

                    <div class="form-group">
                        <label for="modal_summary">Summary / Core Facts</label>
                        <textarea name="summary" id="modal_summary" rows="2" class="form-control" placeholder="Key takeaways, highlights, or specifications"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="modal_body">Full Body Content</label>
                        <textarea name="body" id="modal_body" rows="4" class="form-control" placeholder="Article body, product story, or gameplay description"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="modal_source_url">Source URL</label>
                            <input type="url" name="source_url" id="modal_source_url" class="form-control" placeholder="https://brand.com/item-page">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="modal_featured_image">Featured Image URL</label>
                            <input type="url" name="featured_image" id="modal_featured_image" class="form-control" placeholder="https://brand.com/images/cover.jpg">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save to Library</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
