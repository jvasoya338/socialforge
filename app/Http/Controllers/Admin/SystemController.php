<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\BrandContext;
use App\Services\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function __construct(
        protected BrandContext $brandContext,
        protected SystemHealthService $healthService,
    ) {}

    public function users(Request $request): View
    {
        Gate::authorize('manage-system');

        $users = User::with('currentBrand')->orderBy('name')->paginate(15);

        return view('admin.system.users', compact('users'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_EDITOR, User::ROLE_VIEWER])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        ActivityLog::record(
            action: 'user.created',
            description: "Created user '{$user->name}' ({$user->role})",
            subject: $user,
        );

        return back()->with('success', "User '{$user->name}' created.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-system');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_EDITOR, User::ROLE_VIEWER])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        ActivityLog::record(
            action: 'user.updated',
            description: "Updated user '{$user->name}'",
            subject: $user,
        );

        return back()->with('success', "User '{$user->name}' updated.");
    }

    public function notifications(): View
    {
        $notifications = Auth::user()->notifications()->paginate(20);

        return view('admin.system.notifications', compact('notifications'));
    }

    public function markNotificationRead(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function webhooks(Request $request): View
    {
        Gate::authorize('manage-system');

        $query = WebhookEvent::query();

        if ($provider = $request->input('provider')) {
            $query->where('provider', $provider);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $webhooks = $query->latest()->paginate(20)->withQueryString();

        return view('admin.system.webhooks', compact('webhooks'));
    }

    public function activityLogs(Request $request): View
    {
        Gate::authorize('manage-system');

        $query = $this->brandContext->apply(
            ActivityLog::with(['brand', 'user']),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return view('admin.system.activity-logs', compact('logs'));
    }

    public function settings(): View
    {
        Gate::authorize('manage-system');

        $settings = SystemSetting::all()->keyBy('key');

        return view('admin.system.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system');

        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'token_refresh_threshold_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'max_publishing_retries' => ['required', 'integer', 'min:1', 'max:10'],
            'default_require_approval' => ['nullable', 'boolean'],
        ]);

        SystemSetting::setValue('platform_name', $data['platform_name'], 'general', 'string');
        SystemSetting::setValue('token_refresh_threshold_hours', (string) $data['token_refresh_threshold_hours'], 'security', 'integer');
        SystemSetting::setValue('max_publishing_retries', (string) $data['max_publishing_retries'], 'publishing', 'integer');
        SystemSetting::setValue('default_require_approval', ! empty($data['default_require_approval']) ? 'true' : 'false', 'publishing', 'boolean');

        ActivityLog::record(
            action: 'settings.updated',
            description: 'Updated system settings',
        );

        return back()->with('success', 'System settings saved.');
    }

    public function queueHealth(): View
    {
        Gate::authorize('manage-system');

        $pendingJobs = DB::table('jobs')->get();
        $failedJobs = DB::table('failed_jobs')->latest()->get();

        return view('admin.system.queue-health', compact('pendingJobs', 'failedJobs'));
    }

    public function retryFailedJob(string $id): RedirectResponse
    {
        Gate::authorize('manage-system');

        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('success', "Dispatched retry for failed job ID {$id}.");
    }

    public function flushFailedJobs(): RedirectResponse
    {
        Gate::authorize('manage-system');

        Artisan::call('queue:flush');

        return back()->with('success', 'Flushed all failed queue jobs.');
    }

    public function systemHealth(): View
    {
        Gate::authorize('manage-system');

        $health = $this->healthService->checkAll();

        return view('admin.system.system-health', compact('health'));
    }
}
