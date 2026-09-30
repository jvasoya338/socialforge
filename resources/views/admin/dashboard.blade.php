@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Operational Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<!-- Stat Cards Row -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total_brands'] }}</h3>
                <p>Total Brands</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
            <a href="{{ route('admin.brands.index') }}" class="small-box-footer">View Brands <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['connected_accounts'] }}</h3>
                <p>Connected Accounts</p>
            </div>
            <div class="icon"><i class="fas fa-plug"></i></div>
            <a href="{{ route('admin.social-accounts.connections') }}" class="small-box-footer">View Connections <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $stats['draft_posts'] }}</h3>
                <p>Draft Posts</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
            <a href="{{ route('admin.publishing.drafts') }}" class="small-box-footer">View Drafts <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['awaiting_approval'] }}</h3>
                <p>Awaiting Approval</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a href="{{ route('admin.publishing.approval-queue') }}" class="small-box-footer">Review Queue <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['scheduled_today'] }}</h3>
                <p>Scheduled Today</p>
            </div>
            <div class="icon"><i class="far fa-calendar-check"></i></div>
            <a href="{{ route('admin.publishing.scheduled') }}" class="small-box-footer">View Schedule <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-teal">
            <div class="inner">
                <h3>{{ $stats['published_today'] }}</h3>
                <p>Published Today</p>
            </div>
            <div class="icon"><i class="fas fa-paper-plane"></i></div>
            <a href="{{ route('admin.publishing.published') }}" class="small-box-footer">View Published <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['failed_posts'] }}</h3>
                <p>Failed Posts</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            <a href="{{ route('admin.publishing.failed') }}" class="small-box-footer">View Failures <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-maroon">
            <div class="inner">
                <h3>{{ $stats['credential_problems'] }}</h3>
                <p>Credential Problems</p>
            </div>
            <div class="icon"><i class="fas fa-heartbeat"></i></div>
            <a href="{{ route('admin.credential-health.index') }}" class="small-box-footer">Credential Health <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Main Operational Grid -->
<div class="row">
    <!-- Left Column: Publishing & Failures -->
    <div class="col-lg-7">
        <!-- Upcoming Scheduled Posts -->
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="far fa-calendar-alt text-primary mr-2"></i> Upcoming Scheduled Posts</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.publishing.calendar') }}" class="btn btn-sm btn-tool"><i class="fas fa-external-link-alt"></i> Calendar</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Brand</th>
                                <th>Platform</th>
                                <th>Post Copy</th>
                                <th>Scheduled (Brand Time)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingScheduled as $post)
                                <tr>
                                    <td><strong>{{ $post->brand->name }}</strong></td>
                                    <td><i class="{{ $post->socialPlatform->icon }} mr-1"></i> {{ $post->socialPlatform->name }}</td>
                                    <td class="text-truncate" style="max-width: 250px;">{{ $post->body }}</td>
                                    <td><span class="badge badge-light border">{{ $post->scheduledInBrandTimezone() }}</span></td>
                                    <td>
                                        <form action="{{ route('admin.publishing.posts.publish-now', $post) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-success" title="Publish Now">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="far fa-calendar-times fa-2x mb-2 d-block"></i>
                                        No upcoming scheduled posts. Ready to create or schedule new content.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Publishing Activity -->
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-history text-secondary mr-2"></i> Recent Publishing Activity</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.publishing.published') }}" class="btn btn-sm btn-tool">All Published</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Brand</th>
                                <th>Platform</th>
                                <th>Status</th>
                                <th>Attempt</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPublishingAttempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->socialPost?->brand?->name ?? '—' }}</td>
                                    <td>{{ $attempt->socialPost?->socialPlatform?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $attempt->status === 'success' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($attempt->status) }}
                                        </span>
                                    </td>
                                    <td>#{{ $attempt->attempt_number }}</td>
                                    <td><small class="text-muted">{{ $attempt->created_at->diffForHumans() }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No publishing attempts recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Failed Publishing Attempts (Actionable) -->
        <div class="card card-outline card-danger shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Failed Publishing Attempts</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.publishing.failed') }}" class="btn btn-sm btn-tool">Manage Failures</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Error</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($failedAttempts as $fail)
                                <tr>
                                    <td>{{ $fail->socialPost?->brand?->name }}</td>
                                    <td><span class="badge badge-danger">{{ $fail->failure_category }}</span></td>
                                    <td class="text-truncate" style="max-width: 250px;">{{ $fail->error_message }}</td>
                                    <td>
                                        @if($fail->socialPost)
                                            <form action="{{ route('admin.publishing.posts.retry', $fail->socialPost) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-danger">Retry</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-success">
                                        <i class="fas fa-check-circle mr-1"></i> No failed attempts. All publishing pipelines operational.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Connections, AI, Brand Overview & Activity -->
    <div class="col-lg-5">
        <!-- Social Connection Health -->
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-heartbeat text-success mr-2"></i> Social Connection Health</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.credential-health.index') }}" class="btn btn-sm btn-tool">Full Report</a>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($socialHealth as $acc)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <i class="{{ $acc->socialPlatform->icon }} mr-2"></i>
                                <strong>{{ $acc->brand->name }}</strong> — {{ $acc->account_name }}
                            </div>
                            <span class="badge {{ $acc->healthBadgeClass() }}">{{ $acc->healthLabel() }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-3">No social accounts configured.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- AI Generation Usage -->
        <div class="card card-outline card-purple shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-microchip text-purple mr-2"></i> AI Generation Usage</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.ai.history.index') }}" class="btn btn-sm btn-tool">Audit Log</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <span class="text-muted text-xs text-uppercase d-block">Total Generations</span>
                        <h4 class="font-weight-bold mb-0">{{ number_format($aiUsage['total_generations']) }}</h4>
                    </div>
                    <div class="col-6 mb-3">
                        <span class="text-muted text-xs text-uppercase d-block">Completed Today</span>
                        <h4 class="font-weight-bold mb-0 text-success">{{ number_format($aiUsage['completed_today']) }}</h4>
                    </div>
                    <div class="col-6">
                        <span class="text-muted text-xs text-uppercase d-block">Tokens Processed</span>
                        <h5 class="font-weight-bold mb-0">{{ number_format($aiUsage['total_tokens']) }}</h5>
                    </div>
                    <div class="col-6">
                        <span class="text-muted text-xs text-uppercase d-block">Est. AI Cost</span>
                        <h5 class="font-weight-bold mb-0">${{ number_format($aiUsage['estimated_cost'], 4) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Brand Performance Overview -->
        <div class="card card-outline card-info shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-building text-info mr-2"></i> Brand Performance Overview</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-sm btn-tool">All Brands</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Brand</th>
                                <th>Content</th>
                                <th>Pending</th>
                                <th>Published</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($brandOverview as $b)
                                <tr>
                                    <td><strong>{{ $b->name }}</strong></td>
                                    <td>{{ $b->contents_count }}</td>
                                    <td><span class="badge badge-warning">{{ $b->pending_posts_count }}</span></td>
                                    <td><span class="badge badge-success">{{ $b->published_posts_count }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity Audit Trail -->
        <div class="card card-outline card-dark shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-clipboard-list text-muted mr-2"></i> Recent Activity</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.system.activity-logs') }}" class="btn btn-sm btn-tool">Full Trail</a>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentActivity as $log)
                        <li class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <strong class="text-xs text-primary">{{ $log->action }}</strong>
                                <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <small class="d-block text-dark">{{ $log->description }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-3">No activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
