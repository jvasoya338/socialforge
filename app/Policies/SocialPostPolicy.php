<?php

namespace App\Policies;

use App\Models\SocialPost;
use App\Models\User;

class SocialPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, SocialPost $post): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canEditContent();
    }

    public function update(User $user, SocialPost $post): bool
    {
        return $user->canEditContent();
    }

    public function approve(User $user, SocialPost $post): bool
    {
        return $user->canApprovePosts();
    }

    public function schedule(User $user, SocialPost $post): bool
    {
        return $user->canApprovePosts();
    }

    public function publish(User $user, SocialPost $post): bool
    {
        return $user->canPublish();
    }

    public function delete(User $user, SocialPost $post): bool
    {
        return $user->isAdmin();
    }
}
