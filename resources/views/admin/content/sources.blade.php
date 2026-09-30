@extends('layouts.admin')

@section('title', 'Content Sources')
@section('page_title', 'Content Ingestion Sources')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.content.index') }}">Content</a></li>
    <li class="breadcrumb-item active">Sources</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Configured Ingestion Feeds & APIs</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Brand</th>
                                <th>Source Name</th>
                                <th>Type</th>
                                <th>API Key Status</th>
                                <th>Last Used / Synced</th>
                                <th class="text-right">Key Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sources as $src)
                                <tr>
                                    <td><strong>{{ $src->brand->name }}</strong></td>
                                    <td>
                                        <strong>{{ $src->name }}</strong>
                                        @if($src->base_url)
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ $src->base_url }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-light border text-uppercase">{{ $src->type }}</span></td>
                                    <td>
                                        @if($src->api_key_hash)
                                            <span class="badge badge-success"><i class="fas fa-key mr-1"></i> Active</span>
                                            <code class="d-block text-xs text-muted">{{ $src->api_key_prefix }}</code>
                                        @else
                                            <span class="badge badge-secondary">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted d-block">Sync: {{ $src->last_synced_at?->diffForHumans() ?? 'Never' }}</small>
                                        <small class="text-muted d-block">Used: {{ $src->api_key_last_used_at?->diffForHumans() ?? 'Never' }}</small>
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @can('manage-credentials')
                                            <form action="{{ route('admin.content.sources.rotate-key', $src) }}" method="POST" class="d-inline" onsubmit="return confirm('Rotate API key for this source? The previous key will stop working immediately.');">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-warning" title="Rotate Key">
                                                    <i class="fas fa-sync-alt mr-1"></i> Rotate Key
                                                </button>
                                            </form>
                                            @if($src->api_key_hash)
                                                <form action="{{ route('admin.content.sources.revoke-key', $src) }}" method="POST" class="d-inline" onsubmit="return confirm('Revoke API key? Callers will not be able to authenticate.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Revoke Key">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No content sources configured yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($sources->hasPages())
                <div class="card-footer bg-light">
                    {{ $sources->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Add Source & API Documentation -->
    <div class="col-md-4">
        @can('manage-system')
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Register New Content Source</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.content.sources.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="src_brand_id">Brand <span class="text-danger">*</span></label>
                        <select name="brand_id" id="src_brand_id" class="form-control" required>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (isset($currentBrand) && $currentBrand?->id === $b->id) ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="src_name">Source Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="src_name" class="form-control" placeholder="e.g. Production Blog API" required>
                    </div>

                    <div class="form-group">
                        <label for="src_type">Source Ingestion Type <span class="text-danger">*</span></label>
                        <select name="type" id="src_type" class="form-control" required>
                            <option value="api">API Endpoint (Server-to-Server)</option>
                            <option value="webhook">Webhook Receiver</option>
                            <option value="rss">RSS / Atom Feed</option>
                            <option value="manual">Manual Entry Only</option>
                            <option value="database">Direct Database Sync</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="src_base_url">Base URL / Feed URL</label>
                        <input type="url" name="base_url" id="src_base_url" class="form-control" placeholder="https://brand.com/api/articles">
                    </div>

                    <div class="form-group">
                        <label for="src_auth_type">Authentication Method</label>
                        <select name="authentication_type" id="src_auth_type" class="form-control">
                            <option value="api_key">Bearer API Key (Hashed)</option>
                            <option value="hmac_signature">HMAC Signature</option>
                            <option value="none">Public / Open</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-plus-circle mr-1"></i> Register & Generate API Key
                    </button>
                </form>
            </div>
        </div>
        @endcan

        <!-- Ingestion API Endpoint Reference -->
        <div class="card card-outline card-dark shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-code mr-1"></i> Ingestion API Reference</h3>
            </div>
            <div class="card-body p-3">
                <p class="text-xs text-muted mb-2">Websites (Gamozy, NewsCrestle, GildedVow) push new content via:</p>
                <div class="bg-dark p-2 rounded mb-2 text-xs font-monospace">
                    POST {{ url('/api/v1/content') }}
                </div>
                <div class="bg-light p-2 rounded mb-2 text-xs font-monospace">
                    Authorization: Bearer &lt;API_KEY&gt;<br>
                    Content-Type: application/json
                </div>
                <small class="text-muted d-block">Keys are generated once, verified by SHA-256 hash, and rate-limited to 60 req/min per source.</small>
            </div>
        </div>
    </div>
</div>
@endsection
