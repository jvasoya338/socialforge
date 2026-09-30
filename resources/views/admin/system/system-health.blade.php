@extends('layouts.admin')

@section('title', 'System Health')
@section('page_title', 'System Health & Operational Status')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">System Health</li>
@endsection

@section('content')
<div class="row">
    <!-- Application & Environment -->
    <div class="col-md-6 mb-4">
        <div class="card card-outline card-primary shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-server mr-2 text-primary"></i> Application & Runtime</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <th>Application Key</th>
                        <td><span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Configured</span></td>
                    </tr>
                    <tr>
                        <th>Environment</th>
                        <td><code>{{ $health['application']['environment'] }}</code></td>
                    </tr>
                    <tr>
                        <th>Debug Mode</th>
                        <td><span class="badge {{ $health['application']['debug'] ? 'badge-warning' : 'badge-success' }}">{{ $health['application']['debug'] ? 'Enabled' : 'Disabled' }}</span></td>
                    </tr>
                    <tr>
                        <th>Laravel Version</th>
                        <td><strong>{{ $health['application']['laravel_version'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>PHP Version</th>
                        <td><strong>{{ $health['application']['php_version'] }}</strong></td>
                    </tr>
                    <tr>
                        <th>App Timezone</th>
                        <td><strong>{{ $health['application']['timezone'] }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Database & Queue -->
    <div class="col-md-6 mb-4">
        <div class="card card-outline card-success shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-database mr-2 text-success"></i> Database & Queues</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <th>Database Status</th>
                        <td>
                            <span class="badge {{ $health['database']['healthy'] ? 'badge-success' : 'badge-danger' }}">
                                {{ $health['database']['healthy'] ? 'Connected' : 'Disconnected' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>DB Driver & Name</th>
                        <td><code>{{ $health['database']['driver'] }} / {{ $health['database']['database'] }}</code></td>
                    </tr>
                    <tr>
                        <th>Queue Connection</th>
                        <td><code>{{ $health['queue']['connection'] }}</code></td>
                    </tr>
                    <tr>
                        <th>Queue Health</th>
                        <td>
                            <span class="badge {{ $health['queue']['healthy'] ? 'badge-success' : 'badge-danger' }}">
                                {{ $health['queue']['message'] }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Redis Cache / Queue</th>
                        <td>
                            <span class="badge {{ $health['redis']['healthy'] ? 'badge-info' : 'badge-danger' }}">
                                {{ $health['redis']['message'] }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Storage Health</th>
                        <td>
                            <span class="badge {{ $health['storage']['healthy'] ? 'badge-success' : 'badge-danger' }}">
                                {{ $health['storage']['message'] }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Scheduler & Cron -->
    <div class="col-md-6 mb-4">
        <div class="card card-outline card-info shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="far fa-clock mr-2 text-info"></i> Scheduler & Recurring Jobs</h3>
            </div>
            <div class="card-body">
                <p class="mb-2">Status: <span class="badge badge-success">Scheduler Configured</span></p>
                <small class="text-muted d-block mb-3">Heartbeat: <strong>{{ $health['scheduler']['last_heartbeat'] }}</strong></small>

                <h6 class="font-weight-bold text-xs text-uppercase text-muted">Configured Scheduled Operations</h6>
                <ul class="list-unstyled text-sm mb-0">
                    <li><i class="fas fa-check text-success mr-2"></i> <code>social:dispatch-due-posts</code> — Every minute (publishing queue)</li>
                    <li><i class="fas fa-check text-success mr-2"></i> <code>social:refresh-tokens</code> — Hourly (credential renewal)</li>
                    <li><i class="fas fa-check text-success mr-2"></i> <code>social:sync-content-sources</code> — Hourly (feed imports)</li>
                    <li><i class="fas fa-check text-success mr-2"></i> <code>social:sync-analytics</code> — Every 6 hours (performance metrics)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- AI Providers Status -->
    <div class="col-md-6 mb-4">
        <div class="card card-outline card-purple shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-microchip mr-2 text-purple"></i> AI Provider Integrations</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Provider</th>
                            <th>Driver</th>
                            <th>Model</th>
                            <th>Auth Configured</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($health['ai_providers'] as $ai)
                            <tr>
                                <td>
                                    <strong>{{ $ai['name'] }}</strong>
                                    @if($ai['is_default']) <span class="badge badge-primary text-xs">Default</span> @endif
                                </td>
                                <td><code>{{ $ai['driver'] }}</code></td>
                                <td><code>{{ $ai['model'] }}</code></td>
                                <td>
                                    <span class="badge {{ $ai['has_key'] ? 'badge-success' : 'badge-warning' }}">
                                        {{ $ai['has_key'] ? 'Ready' : 'Missing Key' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Social Platforms Integration Status -->
<div class="card card-outline card-secondary shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-share-alt mr-2"></i> Social Platform OAuth Integrations</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Platform</th>
                        <th>OAuth App Secrets in .env</th>
                        <th>Connected Accounts</th>
                        <th>Credential Attention Required</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($health['social_integrations'] as $plat)
                        <tr>
                            <td><strong>{{ $plat['platform'] }}</strong></td>
                            <td>
                                @if($plat['app_credentials_configured'])
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Configured in .env</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-exclamation mr-1"></i> Needs Client ID/Secret in .env</span>
                                @endif
                            </td>
                            <td><span class="badge badge-light border">{{ $plat['connected_accounts'] }} active</span></td>
                            <td>
                                @if($plat['problem_accounts'] > 0)
                                    <span class="badge badge-danger">{{ $plat['problem_accounts'] }} account(s) need attention</span>
                                @else
                                    <span class="badge badge-success">0 issues</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
