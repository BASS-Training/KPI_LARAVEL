<?php

namespace App\Http\Controllers\Api;

use App\Models\Position;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PositionController extends ApiController
{
    public function index(Request $request)
    {
        $positions = Position::query()
            ->with('department:id,nama,division_id')
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('division_id'), fn ($q) => $q->whereHas('department', fn ($q2) => $q2->where('division_id', $request->integer('division_id'))))
            ->orderBy('nama')
            ->get();

        return $this->success($positions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'          => ['required', 'string', 'max:255'],
            'kode'          => ['nullable', 'string', 'max:50'],
            'department_id' => ['required', 'exists:departments,id'],
            'level'         => ['nullable', 'string', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $position = Position::create([
            'nama'          => $data['nama'],
            'kode'          => $data['kode'] ?? null,
            'department_id' => $data['department_id'],
            'level'         => $data['level'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return $this->success(
            $position->load('department:id,nama,division_id'),
            'Jabatan berhasil ditambahkan.',
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'nama'          => ['required', 'string', 'max:255'],
            'kode'          => ['nullable', 'string', 'max:50'],
            'department_id' => ['required', 'exists:departments,id'],
            'level'         => ['nullable', 'string', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $position->update($data);

        return $this->success(
            $position->refresh()->load('department:id,nama,division_id'),
            'Jabatan berhasil diperbarui.'
        );
    }

    public function destroy(Position $position)
    {
        // Prevent deletion if employees or KPI components are linked
        if ($position->users()->exists()) {
            return $this->error('Jabatan tidak dapat dihapus karena masih digunakan oleh pegawai.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $position->delete();

        return $this->success(null, 'Jabatan berhasil dihapus.');
    }
}
