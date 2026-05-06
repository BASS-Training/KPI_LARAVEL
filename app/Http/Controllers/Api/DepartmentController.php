<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDepartmentRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends ApiController
{
    #[OA\Get(path: '/departments', summary: 'Daftar departemen', tags: ['Department'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'active_only', in: 'query', schema: new OA\Schema(type: 'boolean'))],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scopedTenantId = $user ? $this->resolveScopedTenantId($user) : null;

        $departments = Department::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($scopedTenantId, fn ($q) => $q->where('tenant_id', $scopedTenantId))
            ->orderBy('nama')
            ->get();

        return $this->success($departments);
    }

    #[OA\Post(path: '/departments', summary: 'Buat departemen baru', tags: ['Department'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nama', 'kode'],
            properties: [
                new OA\Property(property: 'nama', type: 'string', example: 'Keuangan'),
                new OA\Property(property: 'kode', type: 'string', example: 'KEU'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Departemen berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create(array_merge(
            $request->validated(),
            ['tenant_id' => $this->resolveScopedTenantId($request->user())]
        ));

        ActivityLog::record($request->user(), 'create_department', 'Department', $department->id, ['nama' => $department->nama], $request);

        return $this->success($department, 'Departemen berhasil dibuat', Response::HTTP_CREATED);
    }

    #[OA\Put(path: '/departments/{id}', summary: 'Update departemen', tags: ['Department'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'nama', type: 'string'),
            new OA\Property(property: 'kode', type: 'string'),
            new OA\Property(property: 'is_active', type: 'boolean'),
        ])),
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function update(StoreDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->ensureDepartmentAccessible($request->user(), $department);
        $department->update($request->validated());

        ActivityLog::record($request->user(), 'update_department', 'Department', $department->id, ['nama' => $department->nama], $request);

        return $this->success($department->fresh(), 'Departemen berhasil diperbarui');
    }

    #[OA\Delete(path: '/departments/{id}', summary: 'Hapus departemen', tags: ['Department'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 409, description: 'Masih ada data terkait'),
        ]
    )]
    public function destroy(Request $request, Department $department): JsonResponse
    {
        $this->ensureDepartmentAccessible($request->user(), $department);
        $nama = $department->nama;
        $department->delete();

        ActivityLog::record($request->user(), 'delete_department', 'Department', null, ['nama' => $nama], $request);

        return $this->success(null, 'Departemen berhasil dihapus');
    }

    private function ensureDepartmentAccessible(User $actor, Department $department): void
    {
        if ((int) $department->tenant_id !== $this->resolveScopedTenantId($actor)) {
            abort(Response::HTTP_FORBIDDEN, 'Departemen ini berada di tenant lain.');
        }
    }

    private function resolveScopedTenantId(User $actor): int
    {
        $tenantId = app()->bound('current_tenant_id') ? (int) app('current_tenant_id') : 0;

        if ($tenantId > 0) {
            return $tenantId;
        }

        return (int) $actor->tenant_id;
    }
}
