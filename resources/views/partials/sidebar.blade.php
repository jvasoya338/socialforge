<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <i class="fas fa-layer-group text-primary ml-3 mr-2"></i>
        <span class="brand-text font-weight-bold">SocialForge</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Brand Filter Indicator -->
        <div class="sidebar-brand-switch text-center py-2">
            <small class="text-muted text-uppercase d-block mb-1">Active Brand Context</small>
            @if(isset($currentBrand) && $currentBrand)
                <span class="badge badge-primary px-3 py-2 text-wrap d-inline-block">{{ $currentBrand->name }}</span>
            @else
                <span class="badge badge-secondary px-3 py-2 d-inline-block">All Brands (System)</span>
            @endif
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- BRANDS -->
                <li class="nav-header">BRANDS</li>
                <li class="nav-item">
                    <a href="{{ route('admin.brands.index') }}" class="nav-link {{ request()->routeIs('admin.brands.index') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>All Brands</p>
                    </a>
                </li>
                @can('create', App\Models\Brand::class)
                <li class="nav-item">
                    <a href="{{ route('admin.brands.create') }}" class="nav-link {{ request()->routeIs('admin.brands.create') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Add Brand</p>
                    </a>
                </li>
                @endcan

                <!-- CONTENT -->
                <li class="nav-header">CONTENT</li>
                <li class="nav-item">
                    <a href="{{ route('admin.content.sources') }}" class="nav-link {{ request()->routeIs('admin.content.sources') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-rss"></i>
                        <p>Content Sources</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.content.index') }}" class="nav-link {{ request()->routeIs('admin.content.index') || request()->routeIs('admin.content.show') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-newspaper"></i>
                        <p>Content Library</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.ai.generator') }}" class="nav-link {{ request()->routeIs('admin.ai.generator') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-magic text-purple"></i>
                        <p>AI Generator</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.drafts') }}" class="nav-link {{ request()->routeIs('admin.publishing.drafts') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Drafts</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.approval-queue') }}" class="nav-link {{ request()->routeIs('admin.publishing.approval-queue') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-check-double text-warning"></i>
                        <p>Approval Queue</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.content.media') }}" class="nav-link {{ request()->routeIs('admin.content.media') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-photo-video"></i>
                        <p>Media Library</p>
                    </a>
                </li>

                <!-- SOCIAL -->
                <li class="nav-header">SOCIAL</li>
                <li class="nav-item">
                    <a href="{{ route('admin.social-platforms.index') }}" class="nav-link {{ request()->routeIs('admin.social-platforms.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-share-alt"></i>
                        <p>Platforms</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.social-accounts.index') }}" class="nav-link {{ request()->routeIs('admin.social-accounts.index') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Social Accounts</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.social-accounts.connections') }}" class="nav-link {{ request()->routeIs('admin.social-accounts.connections') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-plug"></i>
                        <p>Connections</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.credential-health.index') }}" class="nav-link {{ request()->routeIs('admin.credential-health.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-heartbeat text-danger"></i>
                        <p>Credential Health</p>
                    </a>
                </li>

                <!-- PUBLISHING -->
                <li class="nav-header">PUBLISHING</li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.calendar') }}" class="nav-link {{ request()->routeIs('admin.publishing.calendar') ? 'active' : '' }}">
                        <i class="nav-icon far fa-calendar-alt"></i>
                        <p>Calendar</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.scheduled') }}" class="nav-link {{ request()->routeIs('admin.publishing.scheduled') ? 'active' : '' }}">
                        <i class="nav-icon far fa-clock"></i>
                        <p>Scheduled Posts</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.queue') }}" class="nav-link {{ request()->routeIs('admin.publishing.queue') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-stream"></i>
                        <p>Publishing Queue</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.published') }}" class="nav-link {{ request()->routeIs('admin.publishing.published') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-check-circle text-success"></i>
                        <p>Published</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.publishing.failed') }}" class="nav-link {{ request()->routeIs('admin.publishing.failed') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-exclamation-triangle text-danger"></i>
                        <p>Failed Posts</p>
                    </a>
                </li>

                <!-- AI -->
                <li class="nav-header">AI</li>
                <li class="nav-item">
                    <a href="{{ route('admin.ai.providers.index') }}" class="nav-link {{ request()->routeIs('admin.ai.providers.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-microchip"></i>
                        <p>AI Providers</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.ai.brand-voice.index') }}" class="nav-link {{ request()->routeIs('admin.ai.brand-voice.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-comment-dots"></i>
                        <p>Brand Voice</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.ai.prompts.index') }}" class="nav-link {{ request()->routeIs('admin.ai.prompts.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-terminal"></i>
                        <p>Prompt Templates</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.ai.history.index') }}" class="nav-link {{ request()->routeIs('admin.ai.history.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Generation History</p>
                    </a>
                </li>

                <!-- ANALYTICS -->
                <li class="nav-header">ANALYTICS</li>
                <li class="nav-item">
                    <a href="{{ route('admin.analytics.overview') }}" class="nav-link {{ request()->routeIs('admin.analytics.overview') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Overview</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.analytics.brand') }}" class="nav-link {{ request()->routeIs('admin.analytics.brand') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Brand Analytics</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.analytics.platform') }}" class="nav-link {{ request()->routeIs('admin.analytics.platform') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>Platform Analytics</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.analytics.content') }}" class="nav-link {{ request()->routeIs('admin.analytics.content') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>Content Analytics</p>
                    </a>
                </li>

                <!-- SYSTEM -->
                <li class="nav-header">SYSTEM</li>
                @can('manage-system')
                <li class="nav-item">
                    <a href="{{ route('admin.system.users') }}" class="nav-link {{ request()->routeIs('admin.system.users*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <p>Users</p>
                    </a>
                </li>
                @endcan
                <li class="nav-item">
                    <a href="{{ route('admin.system.notifications') }}" class="nav-link {{ request()->routeIs('admin.system.notifications*') ? 'active' : '' }}">
                        <i class="nav-icon far fa-bell"></i>
                        <p>Notifications</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.system.webhooks') }}" class="nav-link {{ request()->routeIs('admin.system.webhooks*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-network-wired"></i>
                        <p>Webhooks</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.system.activity-logs') }}" class="nav-link {{ request()->routeIs('admin.system.activity-logs*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Activity Logs</p>
                    </a>
                </li>
                @can('manage-system')
                <li class="nav-item">
                    <a href="{{ route('admin.system.settings') }}" class="nav-link {{ request()->routeIs('admin.system.settings*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.system.queue-health') }}" class="nav-link {{ request()->routeIs('admin.system.queue-health*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Queue Health</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.system.system-health') }}" class="nav-link {{ request()->routeIs('admin.system.system-health*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-server"></i>
                        <p>System Health</p>
                    </a>
                </li>
                @endcan
            </ul>
        </nav>
    </div>
</aside>
