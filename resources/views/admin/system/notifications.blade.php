@extends('layouts.admin')

@section('title', 'Operational Notifications')
@section('page_title', 'Operational Notifications')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">Notifications</li>
@endsection

@section('content')
<div class="card card-outline card-warning shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold"><i class="far fa-bell text-warning mr-2"></i> Operational Alert Stream</h3>
        <div class="card-tools">
            <form action="{{ route('admin.system.notifications.read-all') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-check-double mr-1"></i> Mark All as Read
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Severity</th>
                        <th>Alert Title</th>
                        <th>Message</th>
                        <th>Received</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $n)
                        <tr class="{{ $n->read_at ? '' : 'font-weight-bold table-light' }}">
                            <td>
                                <span class="badge badge-{{ $n->data['severity'] ?? 'warning' }} text-uppercase">
                                    {{ $n->data['severity'] ?? 'warning' }}
                                </span>
                            </td>
                            <td>{{ $n->data['title'] ?? 'Alert' }}</td>
                            <td>{{ $n->data['message'] ?? '' }}</td>
                            <td><small class="text-muted">{{ $n->created_at->diffForHumans() }}</small></td>
                            <td class="text-right text-nowrap">
                                @if(!empty($n->data['action_url']))
                                    <a href="{{ $n->data['action_url'] }}" class="btn btn-xs btn-outline-primary mr-1">View</a>
                                @endif
                                @if(!$n->read_at)
                                    <form action="{{ route('admin.system.notifications.read', $n->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-secondary"><i class="fas fa-check"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="far fa-bell-slash fa-2x mb-2 d-block"></i>
                                No notifications found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($notifications->hasPages())
        <div class="card-footer bg-light">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
