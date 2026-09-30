<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('admin.publishing.calendar') }}" class="nav-link">Calendar</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('admin.ai.generator') }}" class="nav-link"><i class="fas fa-magic text-purple mr-1"></i> AI Generator</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto align-items-center">
        <!-- Global Brand Selector -->
        <li class="nav-item dropdown mr-2">
            <a class="nav-link dropdown-toggle border rounded px-2 py-1 bg-light" href="#" id="brandDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-building text-primary mr-1"></i>
                <strong>{{ isset($currentBrand) && $currentBrand ? $currentBrand->name : 'All Brands' }}</strong>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="brandDropdown">
                <span class="dropdown-header">Filter Workspace by Brand</span>
                <form action="{{ route('admin.switch-brand') }}" method="POST">
                    @csrf
                    <input type="hidden" name="brand_id" value="all">
                    <button type="submit" class="dropdown-item {{ !isset($currentBrand) || !$currentBrand ? 'active font-weight-bold' : '' }}">
                        <i class="fas fa-globe text-secondary mr-2"></i> All Brands (System View)
                    </button>
                </form>
                <div class="dropdown-divider"></div>
                @if(isset($globalBrands))
                    @foreach($globalBrands as $b)
                        <form action="{{ route('admin.switch-brand') }}" method="POST">
                            @csrf
                            <input type="hidden" name="brand_id" value="{{ $b->id }}">
                            <button type="submit" class="dropdown-item {{ isset($currentBrand) && $currentBrand?->id === $b->id ? 'active font-weight-bold' : '' }}">
                                <i class="fas fa-circle text-xs mr-2 {{ $b->status === 'active' ? 'text-success' : 'text-muted' }}"></i>
                                {{ $b->name }}
                            </button>
                        </form>
                    @endforeach
                @endif
            </div>
        </li>

        <!-- Notifications Dropdown -->
        @php
            $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
            $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->take(5)->get() : collect();
        @endphp
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                @if($unreadCount > 0)
                    <span class="badge badge-warning navbar-badge">{{ $unreadCount }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-header">{{ $unreadCount }} Unread Notifications</span>
                <div class="dropdown-divider"></div>
                @forelse($unreadNotifications as $n)
                    <a href="{{ $n->data['action_url'] ?? route('admin.system.notifications') }}" class="dropdown-item text-truncate">
                        <i class="fas fa-exclamation-circle text-{{ $n->data['severity'] ?? 'warning' }} mr-2"></i>
                        {{ $n->data['title'] ?? 'Notification' }}
                        <span class="float-right text-muted text-xs">{{ $n->created_at->diffForHumans() }}</span>
                    </a>
                    <div class="dropdown-divider"></div>
                @empty
                    <div class="dropdown-item text-muted text-center py-2">No unread alerts</div>
                    <div class="dropdown-divider"></div>
                @endforelse
                <a href="{{ route('admin.system.notifications') }}" class="dropdown-item dropdown-footer">See All Notifications</a>
            </div>
        </li>

        <!-- User Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <span class="d-none d-md-inline font-weight-bold">{{ auth()->user()->name ?? 'User' }}</span>
                <span class="badge badge-secondary ml-1 text-uppercase">{{ auth()->user()->role ?? 'editor' }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <li class="user-header bg-primary">
                    <p>
                        {{ auth()->user()->name ?? 'User' }}
                        <small>{{ auth()->user()->email ?? '' }}</small>
                        <span class="badge badge-light text-uppercase mt-2">{{ auth()->user()->role ?? 'editor' }}</span>
                    </p>
                </li>
                <li class="user-footer d-flex justify-content-between">
                    <a href="{{ route('admin.system.activity-logs') }}" class="btn btn-default btn-flat">Activity Log</a>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-flat">Sign out</button>
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>
