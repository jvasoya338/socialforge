<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\SocialAuth\TokenRefreshService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshSocialTokenJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $socialAccountId)
    {
        $this->onQueue('default');
    }

    public function handle(TokenRefreshService $tokenRefreshService): void
    {
        $account = SocialAccount::find($this->socialAccountId);
        if (! $account) {
            return;
        }

        $tokenRefreshService->refreshAccountToken($account);
    }
}
