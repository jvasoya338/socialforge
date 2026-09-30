@extends('layouts.admin')

@section('title', 'Social Platforms')
@section('page_title', 'Supported Social Platforms')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Social Platforms</li>
@endsection

@section('content')
<div class="row">
    @foreach($platforms as $p)
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card card-outline {{ $p->status === 'active' ? 'card-primary' : 'card-secondary' }} shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold">
                        <i class="{{ $p->icon }} mr-2"></i> {{ $p->name }}
                    </h3>
                    <span class="badge {{ $p->status === 'active' ? 'badge-success' : 'badge-secondary' }} ml-auto">
                        {{ ucfirst($p->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3 text-sm">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>OAuth App Credentials</span>
                            @if($appConfigStatus[$p->slug] ?? false)
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Configured in .env</span>
                            @else
                                <span class="badge badge-warning"><i class="fas fa-exclamation mr-1"></i> Missing in .env</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Max Text Length</span>
                            <strong>{{ $p->max_text_length ? number_format($p->max_text_length) . ' chars' : 'Unlimited' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Active Accounts</span>
                            <span class="badge badge-light border">{{ $p->social_accounts_count }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Total Posts</span>
                            <span class="badge badge-light border">{{ $p->social_posts_count }}</span>
                        </li>
                    </ul>

                    <div class="mb-3">
                        <small class="text-muted font-weight-bold text-uppercase d-block mb-1">Capabilities</small>
                        @if($p->supports_text) <span class="badge badge-light border mr-1">Text</span> @endif
                        @if($p->supports_image) <span class="badge badge-light border mr-1">Image</span> @endif
                        @if($p->supports_video) <span class="badge badge-light border mr-1">Video</span> @endif
                        @if($p->supports_reels) <span class="badge badge-light border mr-1">Reels</span> @endif
                        @if($p->supports_stories) <span class="badge badge-light border mr-1">Stories</span> @endif
                        @if($p->supports_scheduling) <span class="badge badge-light border mr-1">Scheduling</span> @endif
                        @if($p->supports_analytics) <span class="badge badge-light border mr-1">Analytics</span> @endif
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between">
                    <a href="{{ route('admin.social-accounts.index', ['platform_id' => $p->id]) }}" class="btn btn-xs btn-outline-info">
                        <i class="fas fa-users mr-1"></i> Accounts
                    </a>
                    @can('manage-system')
                        <form action="{{ route('admin.social-platforms.toggle', $p) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-xs {{ $p->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                {{ $p->status === 'active' ? 'Disable Platform' : 'Enable Platform' }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
