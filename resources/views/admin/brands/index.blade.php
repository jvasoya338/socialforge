@extends('layouts.admin')

@section('title', 'All Brands')
@section('page_title', 'Brand Management')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Brands</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Registered Brands</h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.brands.index') }}" class="form-inline mr-2">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search brands..." value="{{ request('search') }}">
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
            </form>
            @can('create', App\Models\Brand::class)
                <a href="{{ route('admin.brands.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus-circle mr-1"></i> Add Brand
                </a>
            @endcan
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Slug</th>
                        <th>Website</th>
                        <th>Timezone</th>
                        <th>Approval Policy</th>
                        <th>Accounts</th>
                        <th>Posts</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                        <tr>
                            <td>
                                <strong>{{ $brand->name }}</strong>
                                @if($brand->description)
                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;">{{ $brand->description }}</small>
                                @endif
                            </td>
                            <td><code>{{ $brand->slug }}</code></td>
                            <td>
                                @if($brand->website_url)
                                    <a href="{{ $brand->website_url }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt text-xs mr-1"></i> Visit</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $brand->timezone }}</td>
                            <td>
                                @if($brand->requiresApproval())
                                    <span class="badge badge-warning"><i class="fas fa-user-check mr-1"></i> Required</span>
                                @else
                                    <span class="badge badge-success"><i class="fas fa-bolt mr-1"></i> Auto-Publish</span>
                                @endif
                                @if($brand->requiresStrictFactualCheck())
                                    <span class="badge badge-danger ml-1" title="Strict Factual News Safety Enabled"><i class="fas fa-shield-alt mr-1"></i> News Safety</span>
                                @endif
                            </td>
                            <td><span class="badge badge-light border">{{ $brand->social_accounts_count }}</span></td>
                            <td><span class="badge badge-light border">{{ $brand->social_posts_count }}</span></td>
                            <td>
                                <span class="badge {{ $brand->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                    {{ ucfirst($brand->status) }}
                                </span>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.brands.show', $brand) }}" class="btn btn-xs btn-outline-info" title="View Brand Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('update', $brand)
                                    <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-xs btn-outline-primary" title="Edit Brand">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <form action="{{ route('admin.brands.toggle', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Toggle status for this brand?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs {{ $brand->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}" title="Toggle Active/Inactive">
                                            <i class="fas {{ $brand->status === 'active' ? 'fa-pause' : 'fa-play' }}"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-building fa-2x mb-2 d-block"></i>
                                No brands found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($brands->hasPages())
        <div class="card-footer bg-light">
            {{ $brands->links() }}
        </div>
    @endif
</div>
@endsection
