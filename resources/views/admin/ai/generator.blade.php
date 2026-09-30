@extends('layouts.admin')

@section('title', 'AI Generator')
@section('page_title', 'AI Content Generator')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.content.index') }}">Content</a></li>
    <li class="breadcrumb-item active">AI Generator</li>
@endsection

@section('content')
<form action="{{ route('admin.ai.generator.run') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Source Selection -->
        <div class="col-md-7">
            <div class="card card-outline card-purple shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-magic text-purple mr-2"></i> Select Source Content for Multi-Platform Social Generation</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ai_brand_id">Brand Context <span class="text-danger">*</span></label>
                        <select name="brand_id" id="ai_brand_id" class="form-control" onchange="window.location='{{ route('admin.ai.generator') }}?brand_id=' + this.value" required>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (isset($selectedBrand) && $selectedBrand?->id === $b->id) ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ai_content_id">Content Item from Library <span class="text-danger">*</span></label>
                        <select name="content_id" id="ai_content_id" class="form-control select2" required>
                            <option value="">— Select an ingested game, article, or product —</option>
                            @foreach($contents as $c)
                                <option value="{{ $c->id }}" {{ request('content_id') == $c->id ? 'selected' : '' }}>
                                    [{{ strtoupper($c->type) }}] {{ $c->title }} ({{ $c->created_at->format('M d') }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Don't see your content? Ingest via API or add manually in the <a href="{{ route('admin.content.index') }}">Content Library</a>.</small>
                    </div>

                    <div class="form-group">
                        <label>Target Social Platforms (Select all to generate tailored variants) <span class="text-danger">*</span></label>
                        <div class="row">
                            @foreach($platforms as $p)
                                <div class="col-sm-4 col-6 mb-2">
                                    <div class="custom-control custom-checkbox border rounded p-2 bg-light">
                                        <input type="checkbox" class="custom-control-input" id="platform_{{ $p->id }}" name="platform_ids[]" value="{{ $p->id }}" checked>
                                        <label class="custom-control-label font-weight-bold" for="platform_{{ $p->id }}">
                                            <i class="{{ $p->icon }} mr-1"></i> {{ $p->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generation Settings & Voice Preview -->
        <div class="col-md-5">
            <div class="card card-outline card-secondary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-sliders-h mr-2"></i> Engine & Quality Options</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ai_provider_id">AI Provider Engine</label>
                        <select name="ai_provider_id" id="ai_provider_id" class="form-control">
                            <option value="">Default Active Provider (Deterministic Structured Fallback if no keys)</option>
                            @foreach($providers as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->name }} ({{ $prov->driver }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="submit_for_approval" name="submit_for_approval" value="1" checked>
                            <label class="custom-control-label font-weight-bold" for="submit_for_approval">Submit directly to Approval Queue</label>
                            <small class="form-text text-muted">If unchecked, generated variants will be saved in Drafts.</small>
                        </div>
                    </div>

                    @if($selectedBrand && $selectedBrand->brandVoiceSetting)
                        <div class="callout callout-info mt-3 p-3">
                            <h6 class="font-weight-bold"><i class="fas fa-comment-dots text-purple mr-1"></i> Active Brand Voice Personality</h6>
                            <small class="d-block text-muted"><strong>Tone:</strong> {{ $selectedBrand->brandVoiceSetting->tone }}</small>
                            <small class="d-block text-muted"><strong>Style:</strong> {{ $selectedBrand->brandVoiceSetting->writing_style }}</small>
                            @if($selectedBrand->requiresStrictFactualCheck())
                                <small class="d-block text-danger font-weight-bold mt-1"><i class="fas fa-shield-alt mr-1"></i> Strict News Factual Guardrails Active</small>
                            @endif
                        </div>
                    @endif

                    <button type="submit" class="btn btn-purple btn-block py-2 mt-4 font-weight-bold shadow-sm">
                        <i class="fas fa-magic mr-2"></i> Run AI Generation & Humanization Pass
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
