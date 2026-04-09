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
        $user  = $request->user();
        $query = KpiComponent::query()->with(['division', 'department']);

        if ($user->isPegawai()) {
            // Pegawai: only show components that match their profile.
            // NULL on a field means "applies to everyone in that dimension".
            $query
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('division_id')
                    ->orWhere('division_id', $user->division_id))
                ->where(fn ($q) => $q->whereNull('department_id')
                    ->orWhere('department_id', $user->department_id))
                ->where(fn ($q) => $q->whereNull('position_id')
                    ->orWhere('position_id', $user->position_id));
        } else {
            // HR / Direktur: show all, optional filters
            $query
                ->when($request->filled('division_id'),   fn ($q) => $q->where('division_id',   $request->integer('division_id')))
                ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
                ->when($request->filled('position_id'),   fn ($q) => $q->where('position_id',   $request->integer('position_id')))
                ->when($request->filled('jabatan'),        fn ($q) => $q->where('jabatan',        $request->string('jabatan')))
                ->when($request->filled('is_active'),      fn ($q) => $q->where('is_active',      $request->boolean('is_active')));
        }

        $components = $query
            ->orderBy('objectives')
            ->paginate((int) $request->input('per_page', 100));

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

        return $this->resource(
            new KpiComponentResource($component->load(['division', 'department'])),
            'Komponen KPI berhasil ditambahkan.',
            Response::HTTP_CREATED
        );
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

        return $this->resource(
            new KpiComponentResource($kpiComponent->refresh()->load(['division', 'department'])),
            'Komponen KPI berhasil diperbarui.'
        );
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
