@extends('layouts.admin')

@section('title', 'System Settings')
@section('page_title', 'System Configuration')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">System</a></li>
    <li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-cogs mr-2"></i> Global SocialForge Settings</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.system.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="platform_name">Platform Display Name</label>
                        <input type="text" name="platform_name" id="platform_name" class="form-control" value="{{ old('platform_name', $settings['platform_name']->value ?? 'SocialForge') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="token_refresh_threshold_hours">Token Refresh Warning Threshold (Hours)</label>
                        <input type="number" name="token_refresh_threshold_hours" id="token_refresh_threshold_hours" class="form-control" value="{{ old('token_refresh_threshold_hours', $settings['token_refresh_threshold_hours']->value ?? 48) }}" min="1" max="168" required>
                        <small class="form-text text-muted">Connected social credentials approaching expiration within this timeframe will be flagged and auto-refreshed by the scheduler.</small>
                    </div>

                    <div class="form-group">
                        <label for="max_publishing_retries">Max Publishing Queue Retries</label>
                        <input type="number" name="max_publishing_retries" id="max_publishing_retries" class="form-control" value="{{ old('max_publishing_retries', $settings['max_publishing_retries']->value ?? 3) }}" min="1" max="10" required>
                        <small class="form-text text-muted">Number of backoff retries for transient network or rate-limit publishing failures before moving to Failed status.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            @php
                                $val = $settings['default_require_approval']->value ?? 'true';
                                $checked = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                            @endphp
                            <input type="checkbox" class="custom-control-input" id="default_require_approval" name="default_require_approval" value="1" {{ $checked ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="default_require_approval">Default Approval Required for Newly Added Brands</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
