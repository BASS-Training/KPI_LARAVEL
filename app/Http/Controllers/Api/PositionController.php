<?php

namespace App\Http\Controllers\Api;

use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class PositionController extends ApiController
{
    #[OA\Get(path: '/positions', summary: 'Daftar jabatan', tags: ['Position'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'active_only', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'department_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function index(Request $request)
    {
        $user = $request->user();
        $scopedTenantId = $user ? $this->resolveScopedTenantId($user) : null;

        $positions = Position::query()
            ->with('department:id,nama,kode')
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($scopedTenantId, fn ($q) => $q->where('tenant_id', $scopedTenantId))
            ->orderBy('nama')
            ->get();

        return $this->success($positions);
    }

    #[OA\Post(path: '/positions', summary: 'Buat jabatan baru', tags: ['Position'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nama', 'department_id'],
            properties: [
                new OA\Property(property: 'nama', type: 'string', example: 'Staff Keuangan'),
                new OA\Property(property: 'kode', type: 'string', example: 'STF-KEU'),
                new OA\Property(property: 'department_id', type: 'integer', example: 1),
                new OA\Property(property: 'level', type: 'string', example: 'Staff'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Jabatan berhasil dibuat')]
    )]
    public function store(Request $request)
    {
        $tenantId = $this->resolveScopedTenantId($request->user());
        $data = $this->validatePositionPayload($request, $tenantId);

        $position = Position::create([
            'tenant_id'     => $tenantId,
            'nama'          => $data['nama'],
            'kode'          => $data['kode'] ?? null,
            'department_id' => $data['department_id'],
            'level'         => $data['level'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return $this->success(
            $position->load('department:id,nama,kode'),
            'Jabatan berhasil ditambahkan.',
            Response::HTTP_CREATED
        );
    }

    #[OA\Put(path: '/positions/{id}', summary: 'Update jabatan', tags: ['Position'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'nama', type: 'string'),
            new OA\Property(property: 'kode', type: 'string'),
            new OA\Property(property: 'department_id', type: 'integer'),
            new OA\Property(property: 'level', type: 'string'),
            new OA\Property(property: 'is_active', type: 'boolean'),
        ])),
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function update(Request $request, Position $position)
    {
        $this->ensurePositionAccessible($request->user(), $position);
        $tenantId = $this->resolveScopedTenantId($request->user());
        $data = $this->validatePositionPayload($request, $tenantId, $position);

        $position->update($data);

        return $this->success(
            $position->refresh()->load('department:id,nama,kode'),
            'Jabatan berhasil diperbarui.'
        );
    }

    #[OA\Delete(path: '/positions/{id}', summary: 'Hapus jabatan', tags: ['Position'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function destroy(Request $request, Position $position)
    {
        $this->ensurePositionAccessible($request->user(), $position);

        if ($position->users()->exists()) {
            return $this->error('Jabatan tidak dapat dihapus karena masih digunakan oleh pegawai.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $position->delete();

        return $this->success(null, 'Jabatan berhasil dihapus.');
    }

    private function validatePositionPayload(Request $request, int $tenantId, ?Position $position = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('positions', 'kode')
                    ->ignore($position?->id)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'level' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function ensurePositionAccessible(User $actor, Position $position): void
    {
        if ((int) $position->tenant_id !== $this->resolveScopedTenantId($actor)) {
            abort(Response::HTTP_FORBIDDEN, 'Jabatan ini berada di tenant lain.');
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
