<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::with(['roles', 'department', 'positionRef'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('nip', 'like', "%{$request->search}%");
            }))
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->orderBy('nama')
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $currentTenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
        $isSuperAdmin    = $request->user()?->hasRole('super_admin');

        $data = $request->validate([
            'nip'             => 'required|string|max:50|unique:users,nip',
            'nama'            => 'required|string|max:255',
            'email'           => 'nullable|email|unique:users,email',
            'jabatan'         => 'required|string|max:100',
            'departemen'      => 'required|string|max:100',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'status_karyawan' => 'required|string|max:50',
            'tanggal_masuk'   => 'required|date',
            'no_hp'           => 'nullable|string|max:20',
            'role'            => 'required|string|exists:' . config('permission.table_names.roles', 'roles') . ',name',
            'password'        => 'required|string|min:8',
            'tenant_id'       => $isSuperAdmin ? 'nullable|exists:tenants,id' : 'prohibited',
        ]);

        $tenantId = $isSuperAdmin && isset($data['tenant_id'])
            ? $data['tenant_id']
            : $currentTenantId;

        unset($data['tenant_id']);

        $user = User::create([
            ...$data,
            'tenant_id' => $tenantId,
            'password'  => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);

        $this->auditService->log('users', 'create', 'User', $user->id, null, [
            'nip'  => $user->nip,
            'nama' => $user->nama,
            'role' => $data['role'],
        ]);

        return response()->json(['message' => 'User created.', 'data' => $user->load('roles')], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user->load(['roles', 'department', 'positionRef'])]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->checkTenantOwnership($user);

        $data = $request->validate([
            'nama'            => 'sometimes|string|max:255',
            'email'           => 'nullable|email|unique:users,email,'.$user->id,
            'jabatan'         => 'sometimes|string|max:100',
            'departemen'      => 'sometimes|string|max:100',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'status_karyawan' => 'sometimes|string|max:50',
            'no_hp'           => 'nullable|string|max:20',
            'role'            => 'sometimes|string',
            'password'        => 'nullable|string|min:8',
        ]);

        $old = $user->only(['nama', 'email', 'jabatan', 'departemen', 'status_karyawan']);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user->update($data);

        $this->auditService->log('users', 'update', 'User', $user->id, $old, $user->fresh()->only(['nama', 'email', 'jabatan']));

        return response()->json(['message' => 'User updated.', 'data' => $user->load('roles')]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->checkTenantOwnership($user);

        $old = $user->only(['nip', 'nama', 'email']);
        $user->delete();

        $this->auditService->log('users', 'delete', 'User', $user->id, $old, null);

        return response()->json(['message' => 'User deleted.']);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::whereNotIn('name', ['super_admin'])->get(['id', 'name']);

        return response()->json(['data' => $roles]);
    }

    public function myTenants(Request $request): JsonResponse
    {
        $user = $request->user();

        $tenants = collect();

        // Primary tenant from users.tenant_id
        if ($user->tenant_id) {
            $primary = Tenant::withoutGlobalScopes()->find($user->tenant_id);
            if ($primary) {
                $tenants->push([
                    'id'          => $primary->id,
                    'tenant_code' => $primary->tenant_code,
                    'tenant_name' => $primary->tenant_name,
                    'status'      => $primary->status,
                    'is_primary'  => true,
                    'role'        => $user->role,
                ]);
            }
        }

        // Additional tenants from user_tenants pivot
        $extra = $user->tenants()->withoutGlobalScopes()->get();
        foreach ($extra as $tenant) {
            if ($tenants->contains('id', $tenant->id)) {
                continue;
            }
            $tenants->push([
                'id'          => $tenant->id,
                'tenant_code' => $tenant->tenant_code,
                'tenant_name' => $tenant->tenant_name,
                'status'      => $tenant->status,
                'is_primary'  => $tenant->pivot->is_primary,
                'role'        => $tenant->pivot->role ?? $user->role,
            ]);
        }

        return response()->json(['data' => $tenants->values()]);
    }

    public function assignToTenant(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'  => 'required|exists:tenants,id',
            'role'       => 'nullable|string|exists:' . config('permission.table_names.roles', 'roles') . ',name',
            'is_primary' => 'nullable|boolean',
        ]);

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($data['tenant_id']);

        // Prevent assigning user to same tenant as their primary
        if ((int) $user->tenant_id === (int) $data['tenant_id']) {
            return response()->json(['message' => 'User already belongs to this tenant as primary.'], 422);
        }

        $user->tenants()->syncWithoutDetaching([
            $data['tenant_id'] => [
                'role'       => $data['role'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
            ],
        ]);

        $this->auditService->log('user_tenants', 'create', 'UserTenant', $user->id, null, [
            'user'   => $user->nama,
            'tenant' => $tenant->tenant_name,
            'role'   => $data['role'] ?? null,
        ]);

        return response()->json([
            'message' => "User {$user->nama} assigned to tenant {$tenant->tenant_name}.",
            'data'    => $user->load(['tenants', 'roles']),
        ]);
    }

    public function removeFromTenant(Request $request, User $user, Tenant $tenant): JsonResponse
    {
        $user->tenants()->detach($tenant->id);

        $this->auditService->log('user_tenants', 'delete', 'UserTenant', $user->id, [
            'tenant' => $tenant->tenant_name,
        ], null);

        return response()->json(['message' => "User {$user->nama} removed from tenant {$tenant->tenant_name}."]);
    }

    private function checkTenantOwnership(User $user): void
    {
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;

        if ($tenantId !== null && (int) $user->tenant_id !== (int) $tenantId) {
            abort(403, 'Access denied to this user.');
        }
    }
}
