<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiIndicatorRepository implements KpiIndicatorRepositoryInterface
{
    public function getByRole(int $roleId): Collection
    {
        return KpiIndicator::query()
            ->where('role_id', $roleId)
            ->orderBy('id')
            ->get();
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return KpiIndicator::query()
            ->where('department_id', $departmentId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Return department-scoped indicators first; fall back to role-based if none found.
     */
    public function getForUser(int $roleId, ?int $departmentId): Collection
    {
        if ($departmentId) {
            $dept = $this->getByDepartment($departmentId);

            if ($dept->isNotEmpty()) {
                return $dept;
            }
        }

        return $this->getByRole($roleId);
    }

    public function findById(int $id): ?KpiIndicator
    {
        return KpiIndicator::query()
            ->with(['role', 'department'])
            ->find($id);
    }

    public function all(): Collection
    {
        return KpiIndicator::query()
            ->with(['role', 'department'])
            ->orderBy('department_id')
            ->orderBy('role_id')
            ->orderBy('id')
            ->get();
    }
}
