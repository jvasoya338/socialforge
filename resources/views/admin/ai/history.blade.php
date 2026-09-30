@extends('layouts.admin')

@section('title', 'AI Generation History')
@section('page_title', 'AI Audit Trail & Generation History')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.ai.generator') }}">AI</a></li>
    <li class="breadcrumb-item active">History</li>
@endsection

@section('content')
<div class="card card-outline card-purple shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-history text-purple mr-2"></i> Auditable AI Generations</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Brand & Content</th>
                        <th>Type & Version</th>
                        <th>Model & Tokens</th>
                        <th>Output Excerpt</th>
                        <th>Status</th>
                        <th>Generated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($generations as $gen)
                        <tr>
                            <td>
                                <strong>{{ $gen->brand->name }}</strong>
                                @if($gen->content)
                                    <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ $gen->content->title }}</small>
                                @endif
                                @if($gen->socialPlatform)
                                    <span class="badge badge-light border"><i class="{{ $gen->socialPlatform->icon }} mr-1"></i> {{ $gen->socialPlatform->name }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light border text-uppercase">{{ $gen->generation_type }}</span>
                                <small class="text-muted d-block font-monospace">prompt v{{ $gen->prompt_version }}</small>
                            </td>
                            <td>
                                <code>{{ $gen->model ?: 'built-in' }}</code>
                                <small class="text-muted d-block">in: {{ $gen->input_tokens }} / out: {{ $gen->output_tokens }}</small>
                            </td>
                            <td style="max-width: 380px;">
                                @if($gen->output)
                                    <p class="mb-0 text-sm text-truncate">{{ $gen->output }}</p>
                                @else
                                    <small class="text-danger">{{ $gen->error_message }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $gen->status === 'completed' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($gen->status) }}
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $gen->created_at->diffForHumans() }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No AI generations recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($generations->hasPages())
        <div class="card-footer bg-light">
            {{ $generations->links() }}
        </div>
    @endif
</div>
@endsection
