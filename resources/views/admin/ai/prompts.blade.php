@extends('layouts.admin')

@section('title', 'Prompt Templates')
@section('page_title', 'Prompt Template Management')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.ai.generator') }}">AI</a></li>
    <li class="breadcrumb-item active">Prompts</li>
@endsection

@section('content')
<div class="row">
    @foreach($prompts as $prompt)
        <div class="col-md-6 mb-4">
            <div class="card card-outline card-purple shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title font-weight-bold">{{ $prompt->name }}</h3>
                        <small class="text-muted d-block font-monospace">purpose: {{ $prompt->purpose }}</small>
                    </div>
                    <div>
                        <span class="badge badge-info">v{{ $prompt->version }}</span>
                        <span class="badge {{ $prompt->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($prompt->status) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai.prompts.update', $prompt) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="text-xs text-uppercase text-muted">System Prompt</label>
                            <textarea name="system_prompt" rows="3" class="form-control text-sm font-monospace" required>{{ $prompt->system_prompt }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="text-xs text-uppercase text-muted">User Prompt Template</label>
                            <textarea name="user_prompt_template" rows="5" class="form-control text-sm font-monospace" required>{{ $prompt->user_prompt_template }}</textarea>
                            <small class="text-muted">Variables: <code>@{{brand_name}}</code>, <code>@{{brand_tone}}</code>, <code>@{{content_title}}</code>, <code>@{{content_summary}}</code>, <code>@{{platform_name}}</code>, <code>@{{voice_examples}}</code></small>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="bump_{{ $prompt->id }}" name="bump_version" value="1">
                                    <label class="custom-control-label text-xs" for="bump_{{ $prompt->id }}">Increment Version (Audit)</label>
                                </div>
                            </div>
                            <div class="col-6 text-right">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn btn-sm btn-purple">
                                    <i class="fas fa-save mr-1"></i> Update Template
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
