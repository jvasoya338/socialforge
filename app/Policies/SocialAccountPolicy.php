<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;

class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, SocialAccount $account): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageCredentials();
    }

    public function update(User $user, SocialAccount $account): bool
    {
        return $user->canManageCredentials();
    }

    public function manageCredentials(User $user, SocialAccount $account): bool
    {
        return $user->canManageCredentials();
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $user->canManageCredentials();
    }
}
