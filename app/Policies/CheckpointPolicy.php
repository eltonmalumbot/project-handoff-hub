<?php

namespace App\Policies;

use App\Models\Checkpoint;
use App\Models\User;

class CheckpointPolicy
{
    public function view(User $user, Checkpoint $checkpoint): bool
    {
        return $user->isAllowedOwner() && $user->id === $checkpoint->project->user_id;
    }
}
