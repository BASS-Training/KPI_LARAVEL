<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreSlaRequest;
use App\Http\Resources\SlaResource;
use App\Models\ActivityLog;
use App\Models\Sla;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SlaController extends ApiController
{
    public function index(Request $request)
    {
        $sla = Sla::query()
            ->when($request->filled('jabatan'), fn ($query) => $query->where('jabatan', $request->string('jabatan')))
            ->orderBy('jabatan')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(SlaResource::collection($sla), $sla);
    }

    public function store(StoreSlaRequest $request)
    {
        $sla = Sla::create($request->validated());

        ActivityLog::record(
            $request->user(),
            'sla.created',
            Sla::class,
            $sla->id,
            ['nama_pekerjaan' => $sla->nama_pekerjaan],
            $request
        );

        return $this->resource(new SlaResource($sla), 'SLA berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreSlaRequest $request, Sla $sla)
    {
        $sla->update($request->validated());

        ActivityLog::record(
            $request->user(),
            'sla.updated',
            Sla::class,
            $sla->id,
            ['nama_pekerjaan' => $sla->nama_pekerjaan],
            $request
        );

        return $this->resource(new SlaResource($sla->refresh()), 'SLA berhasil diperbarui.');
    }

    public function destroy(Request $request, Sla $sla)
    {
        $payload = ['nama_pekerjaan' => $sla->nama_pekerjaan];
        $sla->delete();

        ActivityLog::record(
            $request->user(),
            'sla.deleted',
            Sla::class,
            $sla->id,
            $payload,
            $request
        );

        return $this->success(null, 'SLA berhasil dihapus.');
    }
}
