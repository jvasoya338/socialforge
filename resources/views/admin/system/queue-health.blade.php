@extends('layouts.admin')

@section('title', 'Queue Health')
@section('page_title', 'Queue & Background Workers Health')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">Queue Health</li>
@endsection

@section('content')
<div class="row">
    <!-- Summary -->
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-info"><i class="fas fa-tasks"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Jobs in Buffer</span>
                <span class="info-box-number">{{ $pendingJobs->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon {{ $failedJobs->isEmpty() ? 'bg-success' : 'bg-danger' }}">
                <i class="fas fa-exclamation-triangle"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Failed Jobs</span>
                <span class="info-box-number">{{ $failedJobs->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon bg-secondary"><i class="fas fa-microchip"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Queue Driver</span>
                <span class="info-box-number font-monospace text-uppercase">{{ config('queue.default') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Failed Queue Jobs Table with Retry & Flush -->
<div class="card card-outline card-danger shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-danger"><i class="fas fa-bug mr-2"></i> Failed Queue Jobs</h3>
        @if($failedJobs->isNotEmpty())
            <div class="card-tools">
                <form action="{{ route('admin.system.queue-health.flush') }}" method="POST" class="d-inline" onsubmit="return confirm('Flush all failed jobs?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash mr-1"></i> Flush All Failed
                    </button>
                </form>
            </div>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Job ID</th>
                        <th>Queue</th>
                        <th>Payload Summary</th>
                        <th>Exception</th>
                        <th>Failed At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failedJobs as $job)
                        <tr>
                            <td><code>{{ $job->uuid ?? $job->id }}</code></td>
                            <td><span class="badge badge-light border">{{ $job->queue }}</span></td>
                            <td class="text-truncate" style="max-width: 250px;">
                                <code class="text-xs">{{ substr($job->payload, 0, 150) }}...</code>
                            </td>
                            <td class="text-truncate" style="max-width: 350px;">
                                <small class="text-danger font-weight-bold">{{ substr($job->exception, 0, 200) }}...</small>
                            </td>
                            <td><small class="text-muted">{{ $job->failed_at }}</small></td>
                            <td class="text-right text-nowrap">
                                <form action="{{ route('admin.system.queue-health.retry', $job->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-primary" title="Retry Failed Job">
                                        <i class="fas fa-redo-alt mr-1"></i> Retry
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-success">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                No failed queue jobs! Background workers are executing cleanly.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
