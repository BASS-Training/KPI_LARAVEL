<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;
use Illuminate\Support\Collection;

interface KpiIndicatorRepositoryInterface
{
    public function getByRole(int $roleId): Collection;

    public function getByDepartment(int $departmentId): Collection;

    /** Indicators for a user — prefers department_id match, falls back to role_id. */
    public function getForUser(int $roleId, ?int $departmentId): Collection;

    public function findById(int $id): ?KpiIndicator;

    public function all(): Collection;
}
