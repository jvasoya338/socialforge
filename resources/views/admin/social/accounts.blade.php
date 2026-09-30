@extends('layouts.admin')

@section('title', 'Social Accounts')
@section('page_title', 'Social Accounts')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Social Accounts</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Registered Brand Accounts</h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.social-accounts.index') }}" class="form-inline mr-2">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search account..." value="{{ request('search') }}">
                <select name="platform_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Platforms</option>
                    @foreach($platforms as $p)
                        <option value="{{ $p->id }}" {{ request('platform_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
            </form>
            @can('manage-credentials')
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createAccountModal">
                    <i class="fas fa-plus-circle mr-1"></i> Add Account Slot
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
                        <th>Platform</th>
                        <th>Account Name & Username</th>
                        <th>Type</th>
                        <th>Connection Status</th>
                        <th>Connected At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        <tr>
                            <td><strong>{{ $acc->brand->name }}</strong></td>
                            <td><i class="{{ $acc->socialPlatform->icon }} mr-1"></i> {{ $acc->socialPlatform->name }}</td>
                            <td>
                                <strong>{{ $acc->account_name }}</strong>
                                @if($acc->username)
                                    <small class="text-muted d-block">&#64;{{ $acc->username }}</small>
                                @endif
                            </td>
                            <td><span class="badge badge-light border text-uppercase">{{ $acc->account_type }}</span></td>
                            <td>
                                <span class="badge {{ $acc->healthBadgeClass() }}">
                                    {{ $acc->healthLabel() }}
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $acc->connected_at?->format('M d, Y') ?? 'Never' }}</small></td>
                            <td class="text-right text-nowrap">
                                @if($acc->isConnected())
                                    <form action="{{ route('admin.social-accounts.verify', $acc) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-info" title="Verify Connection">
                                            <i class="fas fa-check"></i> Verify
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.social-accounts.refresh', $acc) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-warning" title="Refresh Token">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.social-accounts.disconnect', $acc) }}" method="POST" class="d-inline" onsubmit="return confirm('Disconnect account and purge stored credentials?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Disconnect">
                                            <i class="fas fa-unlink"></i> Disconnect
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.social-accounts.oauth.initiate', $acc) }}" class="btn btn-xs btn-primary">
                                        <i class="fas fa-plug mr-1"></i> Connect via OAuth
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                No social accounts found. Create an account slot for a brand.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($accounts->hasPages())
        <div class="card-footer bg-light">
            {{ $accounts->links() }}
        </div>
    @endif
</div>

<!-- Modal: Add Account Slot -->
<div class="modal fade" id="createAccountModal" tabindex="-1" role="dialog" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.social-accounts.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="createAccountModalLabel">Add Social Account Slot</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="acc_brand_id">Brand <span class="text-danger">*</span></label>
                        <select name="brand_id" id="acc_brand_id" class="form-control" required>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (isset($currentBrand) && $currentBrand?->id === $b->id) ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="acc_platform_id">Social Platform <span class="text-danger">*</span></label>
                        <select name="social_platform_id" id="acc_platform_id" class="form-control" required>
                            @foreach($platforms as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="acc_name">Account Name / Label <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" id="acc_name" class="form-control" placeholder="e.g. Gamozy Official Page" required>
                    </div>

                    <div class="form-group">
                        <label for="acc_username">Handle / Username</label>
                        <input type="text" name="username" id="acc_username" class="form-control" placeholder="e.g. gamozygames">
                    </div>

                    <div class="form-group">
                        <label for="acc_account_type">Account Type</label>
                        <select name="account_type" id="acc_account_type" class="form-control">
                            <option value="page">Page / Brand Profile</option>
                            <option value="business">Business Account</option>
                            <option value="channel">Channel (YouTube)</option>
                            <option value="profile">Personal / Founder Profile</option>
                        </select>
                    </div>

                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Slot (Not Connected)</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
