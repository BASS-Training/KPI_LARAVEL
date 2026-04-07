<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDivisionRequest;
use App\Http\Resources\DivisionResource;
use App\Models\ActivityLog;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DivisionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Division::query();

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $divisions = $query->orderBy('nama')->get();

        return $this->success(
            DivisionResource::collection($divisions)->resolve(),
            'Data divisi berhasil dimuat'
        );
    }

    public function store(StoreDivisionRequest $request): JsonResponse
    {
        $division = Division::create($request->validated());

        ActivityLog::record($request->user(), 'create_division', 'Division', $division->id, [], $request);

        return $this->success(new DivisionResource($division), 'Divisi berhasil dibuat', 201);
    }

    public function update(StoreDivisionRequest $request, Division $division): JsonResponse
    {
        $division->update($request->validated());

        ActivityLog::record($request->user(), 'update_division', 'Division', $division->id, [], $request);

        return $this->success(new DivisionResource($division), 'Divisi berhasil diperbarui');
    }

    public function destroy(Request $request, Division $division): JsonResponse
    {
        $nama = $division->nama;
        $division->delete();

        ActivityLog::record($request->user(), 'delete_division', 'Division', null, ['nama' => $nama], $request);

        return $this->success(null, 'Divisi berhasil dihapus');
    }
}
