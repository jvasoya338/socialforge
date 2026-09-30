@extends('layouts.admin')

@section('title', 'Webhooks')
@section('page_title', 'Webhook Ingestion Events')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">Webhooks</li>
@endsection

@section('content')
<div class="card card-outline card-info shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-network-wired text-info mr-2"></i> Inbound Webhook Activity</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Provider</th>
                        <th>Event Type & ID</th>
                        <th>HMAC Signature</th>
                        <th>Payload Excerpt</th>
                        <th>Status</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($webhooks as $wh)
                        <tr>
                            <td><strong>{{ ucfirst($wh->provider) }}</strong></td>
                            <td>
                                <code>{{ $wh->event_type }}</code>
                                @if($wh->external_event_id)
                                    <small class="text-muted d-block font-monospace">id: {{ $wh->external_event_id }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $wh->signature_valid ? 'badge-success' : 'badge-danger' }}">
                                    {{ $wh->signature_valid ? 'Verified' : 'Invalid / Missing' }}
                                </span>
                            </td>
                            <td class="text-truncate" style="max-width: 350px;">
                                <code class="text-xs">{{ json_encode($wh->payload) }}</code>
                            </td>
                            <td>
                                <span class="badge {{ match($wh->status) {
                                    'processed' => 'badge-success',
                                    'failed' => 'badge-danger',
                                    default => 'badge-secondary'
                                } }}">{{ ucfirst($wh->status) }}</span>
                                @if($wh->error_message)
                                    <small class="text-danger d-block text-truncate" style="max-width: 200px;">{{ $wh->error_message }}</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $wh->created_at->diffForHumans() }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No webhook events received yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($webhooks->hasPages())
        <div class="card-footer bg-light">{{ $webhooks->links() }}</div>
    @endif
</div>
@endsection
