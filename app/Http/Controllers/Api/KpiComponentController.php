<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreKpiComponentRequest;
use App\Http\Resources\KpiComponentResource;
use App\Models\ActivityLog;
use App\Models\KpiComponent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KpiComponentController extends ApiController
{
    public function index(Request $request)
    {
        $components = KpiComponent::query()
            ->when($request->filled('jabatan'), fn ($query) => $query->where('jabatan', $request->string('jabatan')))
            ->orderBy('jabatan')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(KpiComponentResource::collection($components), $components);
    }

    public function store(StoreKpiComponentRequest $request)
    {
        $component = KpiComponent::create($request->validated());

        ActivityLog::record(
            $request->user(),
            'kpi_component.created',
            KpiComponent::class,
            $component->id,
            ['objectives' => $component->objectives],
            $request
        );

        return $this->resource(new KpiComponentResource($component), 'Komponen KPI berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreKpiComponentRequest $request, KpiComponent $kpiComponent)
    {
        $kpiComponent->update($request->validated());

        ActivityLog::record(
            $request->user(),
            'kpi_component.updated',
            KpiComponent::class,
            $kpiComponent->id,
            ['objectives' => $kpiComponent->objectives],
            $request
        );

        return $this->resource(new KpiComponentResource($kpiComponent->refresh()), 'Komponen KPI berhasil diperbarui.');
    }

    public function destroy(Request $request, KpiComponent $kpiComponent)
    {
        $payload = ['objectives' => $kpiComponent->objectives];
        $kpiComponent->delete();

        ActivityLog::record(
            $request->user(),
            'kpi_component.deleted',
            KpiComponent::class,
            $kpiComponent->id,
            $payload,
            $request
        );

        return $this->success(null, 'Komponen KPI berhasil dihapus.');
    }
}
