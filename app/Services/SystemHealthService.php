<?php

namespace App\Services;

use App\Models\AIProvider;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use App\Models\SystemSetting;
use App\Services\AI\AIProviderManager;
use App\Services\SocialAuth\SocialAuthManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemHealthService
{
    public function __construct(
        protected AIProviderManager $aiProviderManager,
        protected SocialAuthManager $socialAuthManager,
    ) {}

    public function checkAll(): array
    {
        return [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'redis' => $this->checkRedis(),
            'scheduler' => $this->checkScheduler(),
            'storage' => $this->checkStorage(),
            'ai_providers' => $this->checkAIProviders(),
            'social_integrations' => $this->checkSocialIntegrations(),
        ];
    }

    protected function checkApplication(): array
    {
        return [
            'healthy' => ! empty(config('app.key')),
            'environment' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'healthy' => true,
                'driver' => config('database.default'),
                'database' => config('database.connections.'.config('database.default').'.database'),
                'message' => 'Database connection active.',
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'driver' => config('database.default'),
                'database' => null,
                'message' => 'Database unreachable.',
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            return [
                'healthy' => $failedJobs === 0,
                'connection' => config('queue.default'),
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'message' => $failedJobs === 0 ? 'Queue healthy.' : "{$failedJobs} failed job(s) recorded.",
            ];
        } catch (\Throwable) {
            return [
                'healthy' => false,
                'connection' => config('queue.default'),
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'message' => 'Unable to inspect queue tables.',
            ];
        }
    }

    protected function checkRedis(): array
    {
        $queueDriver = config('queue.default');
        $cacheDriver = config('cache.default');
        $isConfigured = $queueDriver === 'redis' || $cacheDriver === 'redis';

        if (! $isConfigured) {
            return [
                'configured' => false,
                'healthy' => true,
                'message' => 'Redis not active as primary driver (using database driver; Redis-ready config available).',
            ];
        }

        try {
            Redis::connection()->ping();

            return [
                'configured' => true,
                'healthy' => true,
                'message' => 'Redis server responding to PING.',
            ];
        } catch (\Throwable) {
            return [
                'configured' => true,
                'healthy' => false,
                'message' => 'Redis server unreachable.',
            ];
        }
    }

    protected function checkScheduler(): array
    {
        $lastHeartbeat = SystemSetting::getValue('scheduler_last_heartbeat');

        return [
            'healthy' => true,
            'last_heartbeat' => $lastHeartbeat ?: 'Not yet triggered by cron',
            'message' => $lastHeartbeat ? "Last ran at {$lastHeartbeat}" : 'Ready (run schedule:run or configure cron)',
        ];
    }

    protected function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default', 'public');
            $writable = is_writable(storage_path('app'));

            return [
                'healthy' => $writable,
                'disk' => $disk,
                'message' => $writable ? "Storage disk '{$disk}' is writable." : 'Storage directory is not writable.',
            ];
        } catch (\Throwable) {
            return [
                'healthy' => false,
                'disk' => 'unknown',
                'message' => 'Storage check failed.',
            ];
        }
    }

    protected function checkAIProviders(): array
    {
        $providers = AIProvider::all();
        $results = [];

        foreach ($providers as $provider) {
            $results[] = [
                'name' => $provider->name,
                'driver' => $provider->driver,
                'model' => $provider->model,
                'status' => $provider->status,
                'is_default' => $provider->is_default,
                'has_key' => $provider->driver === AIProvider::DRIVER_MOCK || $provider->hasApiKey() || ($provider->driver === AIProvider::DRIVER_OPENAI && ! empty(config('services.openai.api_key'))),
            ];
        }

        return $results;
    }

    protected function checkSocialIntegrations(): array
    {
        $platforms = SocialPlatform::all();
        $results = [];

        foreach ($platforms as $platform) {
            try {
                $authProvider = $this->socialAuthManager->provider($platform->slug);
                $appConfigured = $authProvider->hasConfiguredAppCredentials();
            } catch (\Throwable) {
                $appConfigured = false;
            }

            $connectedAccounts = SocialAccount::where('social_platform_id', $platform->id)
                ->whereIn('connection_status', [SocialAccount::CONNECTION_CONNECTED, SocialAccount::CONNECTION_EXPIRING_SOON])
                ->count();

            $problemAccounts = SocialAccount::where('social_platform_id', $platform->id)
                ->whereIn('connection_status', [
                    SocialAccount::CONNECTION_REFRESH_FAILED,
                    SocialAccount::CONNECTION_EXPIRED,
                    SocialAccount::CONNECTION_RECONNECT_REQUIRED,
                ])
                ->count();

            $results[] = [
                'platform' => $platform->name,
                'slug' => $platform->slug,
                'app_credentials_configured' => $appConfigured,
                'connected_accounts' => $connectedAccounts,
                'problem_accounts' => $problemAccounts,
            ];
        }

        return $results;
    }
}
