<?php

namespace App\Policies;

use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, User $employee): bool
    {
        return $user->canManageAllData();
    }

    public function delete(User $user, User $employee): bool
    {
        return $user->canManageAllData() && $user->id !== $employee->id;
    }
}
