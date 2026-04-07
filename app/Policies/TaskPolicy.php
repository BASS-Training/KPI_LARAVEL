<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->canManageAllData() || $task->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->canManageAllData() || $task->user_id === $user->id;
    }

    public function map(User $user): bool
    {
        return $user->canManageAllData();
    }
}
