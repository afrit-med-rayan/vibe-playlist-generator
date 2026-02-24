<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VibeSession;

class VibeSessionPolicy
{
    /**
     * Determine whether the user can view the session.
     */
    public function view(User $user, VibeSession $session): bool
    {
        return $user->id === $session->user_id;
    }

    /**
     * Determine whether the user can update the session.
     */
    public function update(User $user, VibeSession $session): bool
    {
        return $user->id === $session->user_id;
    }
}
