@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('page_title', 'System Audit Trail & Activity Logs')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">Activity Logs</li>
@endsection

@section('content')
<div class="card card-outline card-dark shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-clipboard-list text-muted mr-2"></i> Audit Trail (Redacted Security Log)</h3>
        <div class="card-tools">
            <form method="GET" action="{{ route('admin.system.activity-logs') }}" class="form-inline">
                <input type="text" name="action" class="form-control form-control-sm mr-2" placeholder="Filter action..." value="{{ request('action') }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-filter"></i></button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Brand</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Properties (Sanitized)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td><small class="text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</small></td>
                            <td>
                                @if($log->user)
                                    <strong>{{ $log->user->name }}</strong>
                                    <small class="text-muted d-block">{{ $log->user->role }}</small>
                                @else
                                    <span class="text-muted">System Worker</span>
                                @endif
                            </td>
                            <td>{{ $log->brand?->name ?? 'Global' }}</td>
                            <td><code>{{ $log->action }}</code></td>
                            <td>{{ $log->description }}</td>
                            <td class="text-truncate" style="max-width: 250px;">
                                @if($log->properties)
                                    <code class="text-xs">{{ json_encode($log->properties) }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No activity logs recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-light">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
