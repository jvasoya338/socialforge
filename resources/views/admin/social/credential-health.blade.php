@extends('layouts.admin')

@section('title', 'Credential Health')
@section('page_title', 'Credential Health & Token Lifecycle')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.social-accounts.index') }}">Social</a></li>
    <li class="breadcrumb-item active">Credential Health</li>
@endsection

@section('content')
<div class="card card-outline card-danger shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-heartbeat text-danger mr-2"></i> Token Health & Maintenance</h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.credential-health.index') }}" class="form-inline">
                <select name="connection_status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Health Statuses</option>
                    <option value="connected" {{ request('connection_status') === 'connected' ? 'selected' : '' }}>Healthy</option>
                    <option value="expiring_soon" {{ request('connection_status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                    <option value="refresh_failed" {{ request('connection_status') === 'refresh_failed' ? 'selected' : '' }}>Refresh Failed</option>
                    <option value="expired" {{ request('connection_status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="reconnect_required" {{ request('connection_status') === 'reconnect_required' ? 'selected' : '' }}>Reconnect Required</option>
                    <option value="disconnected" {{ request('connection_status') === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand</th>
                        <th>Platform</th>
                        <th>Account Name</th>
                        <th>Connection</th>
                        <th>Expiration</th>
                        <th>Last Refresh</th>
                        <th>Last Verification</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        @php $cred = $acc->credential; @endphp
                        <tr>
                            <td><strong>{{ $acc->brand->name }}</strong></td>
                            <td><i class="{{ $acc->socialPlatform->icon }} mr-1"></i> {{ $acc->socialPlatform->name }}</td>
                            <td>{{ $acc->account_name }}</td>
                            <td>
                                @if($acc->isConnected())
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($cred?->expires_at)
                                    <span class="{{ $cred->isExpiringSoon() ? 'text-danger font-weight-bold' : 'text-dark' }}">
                                        {{ $cred->expires_at->format('Y-m-d H:i') }}
                                        <small class="d-block text-muted">({{ $cred->expires_at->diffForHumans() }})</small>
                                    </span>
                                @else
                                    <small class="text-muted">Not applicable</small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $cred?->last_refreshed_at?->diffForHumans() ?? 'Never' }}</small>
                                @if($cred?->last_refresh_error)
                                    <small class="text-danger d-block text-truncate" style="max-width: 160px;" title="{{ $cred->last_refresh_error }}">
                                        {{ $cred->last_refresh_error }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $acc->last_verified_at?->diffForHumans() ?? 'Never' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $acc->healthBadgeClass() }}">
                                    {{ $acc->healthLabel() }}
                                </span>
                            </td>
                            <td class="text-right text-nowrap">
                                @if($acc->isConnected())
                                    <form action="{{ route('admin.social-accounts.refresh', $acc) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-warning" title="Attempt Token Refresh">
                                            <i class="fas fa-sync-alt mr-1"></i> Refresh
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.social-accounts.verify', $acc) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-info" title="Verify Connection with Platform API">
                                            <i class="fas fa-check mr-1"></i> Verify
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.social-accounts.oauth.initiate', $acc) }}" class="btn btn-xs btn-primary">
                                        <i class="fas fa-plug mr-1"></i> Reconnect
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-heartbeat fa-2x mb-2 d-block text-danger"></i>
                                No social accounts found for credential inspection.
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
@endsection
