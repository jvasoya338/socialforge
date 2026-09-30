@extends('layouts.admin')

@section('title', 'AI Providers')
@section('page_title', 'AI Providers & Drivers')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.ai.generator') }}">AI</a></li>
    <li class="breadcrumb-item active">Providers</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Registered AI Providers</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Name & Driver</th>
                                <th>Model</th>
                                <th>Status</th>
                                <th>API Key / Auth</th>
                                <th>Last Health Test</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providers as $prov)
                                <tr>
                                    <td>
                                        <strong>{{ $prov->name }}</strong>
                                        @if($prov->is_default)
                                            <span class="badge badge-primary ml-1">Default</span>
                                        @endif
                                        <small class="text-muted d-block text-uppercase font-monospace">{{ $prov->driver }}</small>
                                    </td>
                                    <td><code>{{ $prov->model }}</code></td>
                                    <td>
                                        <span class="badge {{ $prov->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ ucfirst($prov->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($prov->driver === 'mock')
                                            <span class="badge badge-light border">No Key Required (Built-in)</span>
                                        @elseif($prov->hasApiKey() || ($prov->driver === 'openai' && !empty(config('services.openai.api_key'))))
                                            <span class="badge badge-success"><i class="fas fa-lock mr-1"></i> Encrypted Key Set</span>
                                        @else
                                            <span class="badge badge-warning">Missing Key</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($prov->last_health_status)
                                            <span class="badge {{ $prov->last_health_status === 'healthy' ? 'badge-success' : 'badge-danger' }}">
                                                {{ ucfirst($prov->last_health_status) }}
                                            </span>
                                            <small class="text-muted d-block">{{ $prov->last_tested_at?->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Not tested yet</small>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @can('manage-system')
                                            <form action="{{ route('admin.ai.providers.test', $prov) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-info" title="Test Connectivity">
                                                    <i class="fas fa-bolt mr-1"></i> Test
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Register AI Provider -->
    <div class="col-md-4">
        @can('manage-system')
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Add AI Provider</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ai.providers.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="ai_name">Provider Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="ai_name" class="form-control" placeholder="e.g. OpenAI GPT-4o, Ollama Local" required>
                    </div>

                    <div class="form-group">
                        <label for="ai_slug">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="ai_slug" class="form-control" placeholder="e.g. openai-prod" required>
                    </div>

                    <div class="form-group">
                        <label for="ai_driver">Driver <span class="text-danger">*</span></label>
                        <select name="driver" id="ai_driver" class="form-control" required>
                            <option value="openai">OpenAI (or OpenAI-compatible)</option>
                            <option value="ollama">Ollama (Local LLM)</option>
                            <option value="mock">SocialForge Structured Engine</option>
                            <option value="custom">Custom HTTP Driver</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ai_model">Model Identifier <span class="text-danger">*</span></label>
                        <input type="text" name="model" id="ai_model" class="form-control" placeholder="e.g. gpt-4o-mini, llama3.1" required>
                    </div>

                    <div class="form-group">
                        <label for="ai_base_url">Base URL (Optional)</label>
                        <input type="url" name="base_url" id="ai_base_url" class="form-control" placeholder="https://api.openai.com/v1">
                    </div>

                    <div class="form-group">
                        <label for="ai_key">API Key (Stored with AES-256 Encryption)</label>
                        <input type="password" name="api_key" id="ai_key" class="form-control" placeholder="sk-...">
                        <small class="form-text text-muted">Never exposed in views, logs, or javascript.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_default_prov" name="is_default" value="1">
                            <label class="custom-control-label" for="is_default_prov">Set as Default Generation Provider</label>
                        </div>
                    </div>

                    <input type="hidden" name="status" value="active">

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-plus-circle mr-1"></i> Register Provider
                    </button>
                </form>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection
