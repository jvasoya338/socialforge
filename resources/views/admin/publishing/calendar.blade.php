@extends('layouts.admin')

@section('title', 'Publishing Calendar')
@section('page_title', 'Publishing Calendar')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.scheduled') }}">Publishing</a></li>
    <li class="breadcrumb-item active">Calendar</li>
@endsection

@section('content')
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="far fa-calendar-alt text-primary mr-2"></i> Scheduled Publishing Timeline
        </h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.publishing.calendar') }}" class="form-inline">
                <input type="month" name="month" class="form-control form-control-sm mr-2" value="{{ $month }}" onchange="this.form.submit()">
                <select name="brand_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Brands</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="card-body">
        <p class="text-sm text-muted">
            Displaying posts scheduled in timezone: <strong>{{ $tz }}</strong>.
        </p>

        @if($scheduledPosts->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="far fa-calendar-times fa-3x mb-3 d-block"></i>
                No scheduled posts found for {{ date('F Y', strtotime($month.'-01')) }}.
            </div>
        @else
            <div class="timeline">
                @php $currentDay = null; @endphp
                @foreach($scheduledPosts as $post)
                    @php
                        $postDay = $post->scheduled_at ? $post->scheduled_at->copy()->setTimezone($tz)->format('l, F j, Y') : 'Unscheduled';
                    @endphp

                    @if($currentDay !== $postDay)
                        @php $currentDay = $postDay; @endphp
                        <div class="time-label">
                            <span class="bg-primary px-3 py-1 font-weight-bold">{{ $postDay }}</span>
                        </div>
                    @endif

                    <div>
                        <i class="{{ $post->socialPlatform->icon }} bg-info"></i>
                        <div class="timeline-item shadow-sm">
                            <span class="time"><i class="far fa-clock mr-1"></i> {{ $post->scheduled_at?->copy()->setTimezone($tz)->format('H:i T') }}</span>
                            <h3 class="timeline-header font-weight-bold">
                                <a href="#">{{ $post->brand->name }}</a> — {{ $post->socialPlatform->name }}
                                <span class="badge {{ $post->statusBadgeClass() }} ml-2">{{ ucfirst($post->status) }}</span>
                            </h3>
                            <div class="timeline-body">
                                {{ $post->body }}
                                @if(!empty($post->hashtags))
                                    <small class="text-primary font-weight-bold d-block mt-1">{{ implode(' ', array_map(fn($t) => '#'.ltrim($t, '#'), $post->hashtags)) }}</small>
                                @endif
                            </div>
                            <div class="timeline-footer">
                                @if($post->status === 'scheduled')
                                    <form action="{{ route('admin.publishing.posts.publish-now', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-success"><i class="fas fa-paper-plane mr-1"></i> Publish Now</button>
                                    </form>
                                    <form action="{{ route('admin.publishing.posts.cancel', $post) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-times mr-1"></i> Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                <div><i class="far fa-clock bg-gray"></i></div>
            </div>
        @endif
    </div>
</div>
@endsection
