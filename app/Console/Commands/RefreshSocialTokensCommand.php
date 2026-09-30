<?php

namespace App\Console\Commands;

use App\Jobs\RefreshSocialTokenJob;
use App\Services\SocialAuth\TokenRefreshService;
use Illuminate\Console\Command;

class RefreshSocialTokensCommand extends Command
{
    protected $signature = 'social:refresh-tokens {--sync : Run token refresh synchronously instead of dispatching jobs}';

    protected $description = 'Inspect connected social accounts with expiring credentials and refresh tokens';

    public function handle(TokenRefreshService $tokenRefreshService): int
    {
        $expiringCredentials = $tokenRefreshService->getExpiringCredentials();

        $count = 0;
        foreach ($expiringCredentials as $credential) {
            if (! $credential->socialAccount) {
                continue;
            }

            if ($this->option('sync')) {
                $tokenRefreshService->refreshAccountToken($credential->socialAccount);
            } else {
                RefreshSocialTokenJob::dispatch($credential->social_account_id);
            }
            $count++;
        }

        $this->info("Processed {$count} expiring social credential(s).");

        return self::SUCCESS;
    }
}
