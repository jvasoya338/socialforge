@extends('layouts.admin')

@section('title', 'Social Connections')
@section('page_title', 'Social Connections Matrix')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.social-accounts.index') }}">Social</a></li>
    <li class="breadcrumb-item active">Connections</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Brand & Platform Connection Matrix</h3>
        <div class="card-tools">
            <a href="{{ route('admin.credential-health.index') }}" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-heartbeat mr-1"></i> Credential Health
            </a>
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
                        <th>Connection Status</th>
                        <th>Token Validity</th>
                        <th>Connected Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        <tr>
                            <td><strong>{{ $acc->brand->name }}</strong></td>
                            <td><i class="{{ $acc->socialPlatform->icon }} mr-1"></i> {{ $acc->socialPlatform->name }}</td>
                            <td>{{ $acc->account_name }}</td>
                            <td><span class="badge {{ $acc->healthBadgeClass() }}">{{ $acc->healthLabel() }}</span></td>
                            <td>
                                @if($acc->credential?->expires_at)
                                    <small class="text-muted d-block">Expires: {{ $acc->credential->expires_at->diffForHumans() }}</small>
                                @else
                                    <small class="text-muted">No expiration / Not connected</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $acc->connected_at?->format('M d, Y') ?? '—' }}</small></td>
                            <td class="text-right text-nowrap">
                                @if($acc->isConnected())
                                    <form action="{{ route('admin.social-accounts.verify', $acc) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-info">Verify</button>
                                    </form>
                                    <form action="{{ route('admin.social-accounts.disconnect', $acc) }}" method="POST" class="d-inline" onsubmit="return confirm('Disconnect account?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger">Disconnect</button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.social-accounts.oauth.initiate', $acc) }}" class="btn btn-xs btn-primary">
                                        <i class="fas fa-plug mr-1"></i> Connect
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No social connections found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
