@extends('layouts.admin')

@section('title', $brand->name)
@section('page_title', $brand->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.brands.index') }}">Brands</a></li>
    <li class="breadcrumb-item active">{{ $brand->name }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Left Column: Brand Overview & Connected Accounts -->
    <div class="col-md-5">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Brand Profile</h3>
                <div class="card-tools">
                    @can('update', $brand)
                        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-pencil-alt mr-1"></i> Edit
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h3 class="font-weight-bold mb-1">{{ $brand->name }}</h3>
                    <span class="badge badge-light border font-monospace">slug: {{ $brand->slug }}</span>
                    <span class="badge {{ $brand->status === 'active' ? 'badge-success' : 'badge-secondary' }} ml-1">{{ ucfirst($brand->status) }}</span>
                </div>

                <p class="text-muted text-center">{{ $brand->description ?: 'No description provided.' }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Website</span>
                        @if($brand->website_url)
                            <a href="{{ $brand->website_url }}" target="_blank" rel="noopener">{{ $brand->website_url }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Timezone</span>
                        <strong>{{ $brand->timezone }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Default Language</span>
                        <strong>{{ strtoupper($brand->default_language) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Approval Required</span>
                        <span class="badge {{ $brand->requiresApproval() ? 'badge-warning' : 'badge-success' }}">
                            {{ $brand->requiresApproval() ? 'Yes (Review Queue)' : 'No (Auto-Publish)' }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>News Factual Safeguards</span>
                        <span class="badge {{ $brand->requiresStrictFactualCheck() ? 'badge-danger' : 'badge-secondary' }}">
                            {{ $brand->requiresStrictFactualCheck() ? 'Enabled' : 'Disabled' }}
                        </span>
                    </li>
                </ul>

                <form action="{{ route('admin.switch-brand') }}" method="POST">
                    @csrf
                    <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter mr-1"></i> Switch Active Workspace to {{ $brand->name }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Social Accounts for this Brand -->
        <div class="card card-outline card-info shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-share-alt text-info mr-2"></i> Social Accounts</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.social-accounts.index', ['brand_id' => $brand->id]) }}" class="btn btn-sm btn-tool">Manage</a>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($brand->socialAccounts as $acc)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <i class="{{ $acc->socialPlatform->icon }} mr-2"></i>
                                <strong>{{ $acc->account_name }}</strong>
                            </div>
                            <span class="badge {{ $acc->healthBadgeClass() }}">{{ $acc->healthLabel() }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-3">No social accounts configured for this brand.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Brand Voice & Content Sources -->
    <div class="col-md-7">
        <!-- Brand Voice Profile -->
        <div class="card card-outline card-purple shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-comment-dots text-purple mr-2"></i> Brand Voice Engine Configuration</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.ai.brand-voice.index', ['brand_id' => $brand->id]) }}" class="btn btn-sm btn-tool">Configure Voice</a>
                </div>
            </div>
            <div class="card-body">
                @if($brand->brandVoiceSetting)
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Tone & Persona</dt>
                        <dd class="col-sm-8 font-weight-bold">{{ $brand->brandVoiceSetting->tone }}</dd>

                        <dt class="col-sm-4 text-muted">Writing Style</dt>
                        <dd class="col-sm-8">{{ $brand->brandVoiceSetting->writing_style }}</dd>

                        <dt class="col-sm-4 text-muted">Target Audience</dt>
                        <dd class="col-sm-8">{{ $brand->brandVoiceSetting->audience ?: 'General' }}</dd>

                        <dt class="col-sm-4 text-muted">Forbidden Words</dt>
                        <dd class="col-sm-8">
                            @forelse($brand->brandVoiceSetting->words_to_avoid ?? [] as $w)
                                <span class="badge badge-light border text-danger mr-1">{{ $w }}</span>
                            @empty
                                <span class="text-muted">None specified</span>
                            @endforelse
                        </dd>

                        <dt class="col-sm-4 text-muted">Preferred Phrases</dt>
                        <dd class="col-sm-8">
                            @forelse($brand->brandVoiceSetting->preferred_phrases ?? [] as $p)
                                <span class="badge badge-light border text-success mr-1">{{ $p }}</span>
                            @empty
                                <span class="text-muted">None specified</span>
                            @endforelse
                        </dd>

                        <dt class="col-sm-4 text-muted">Instructions</dt>
                        <dd class="col-sm-8"><small class="text-muted">{{ $brand->brandVoiceSetting->additional_instructions ?: 'None' }}</small></dd>
                    </dl>
                @else
                    <p class="text-muted">No voice profile configured yet.</p>
                @endif
            </div>
        </div>

        <!-- Content Sources -->
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-rss text-secondary mr-2"></i> Ingestion Sources & API Integrations</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.content.sources', ['brand_id' => $brand->id]) }}" class="btn btn-sm btn-tool">Manage Sources</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Source Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>API Key Prefix</th>
                                <th>Last Sync</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($brand->contentSources as $source)
                                <tr>
                                    <td><strong>{{ $source->name }}</strong></td>
                                    <td><span class="badge badge-light border text-uppercase">{{ $source->type }}</span></td>
                                    <td>
                                        <span class="badge {{ $source->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ ucfirst($source->status) }}
                                        </span>
                                    </td>
                                    <td><code>{{ $source->api_key_prefix ?: 'Not Generated' }}</code></td>
                                    <td><small class="text-muted">{{ $source->last_synced_at?->diffForHumans() ?? 'Never' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No content sources configured for this brand.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
