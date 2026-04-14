<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\Role;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class KpiIndicatorController extends ApiController
{
    public function __construct(
        private readonly KpiIndicatorRepositoryInterface $indicatorRepository,
    ) {
    }

    /** GET /kpi-indicators */
    public function index(Request $request): JsonResponse
    {
        $indicators = KpiIndicator::query()
            ->with(['role', 'department'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->integer('role_id')))
            ->orderBy('department_id')
            ->orderBy('role_id')
            ->orderBy('id')
            ->get()
            ->map(fn (KpiIndicator $ind) => [
                'id'                   => $ind->id,
                'name'                 => $ind->name,
                'description'          => $ind->description,
                'weight'               => (float) $ind->weight,
                'default_target_value' => (float) $ind->default_target_value,
                'formula'              => $ind->formula,
                'formula_type_label'   => $ind->getFormulaTtypeLabel(),
                'role_id'              => $ind->role_id,
                'role'                 => $ind->role ? ['id' => $ind->role->id, 'name' => $ind->role->name] : null,
                'department_id'        => $ind->department_id,
                'department'           => $ind->department ? ['id' => $ind->department->id, 'nama' => $ind->department->nama] : null,
            ]);

        return $this->success(['items' => $indicators]);
    }

    /** POST /kpi-indicators */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'weight'               => ['required', 'numeric', 'min:0', 'max:100'],
            'default_target_value' => ['nullable', 'numeric', 'min:0'],
            'formula'              => ['nullable', 'array'],
            'formula.type'         => ['required_with:formula', Rule::in(['percentage', 'conditional', 'threshold', 'zero_penalty', 'flat'])],
            'formula.thresholds'   => ['required_if:formula.type,threshold', 'array'],
            'formula.score'        => ['required_if:formula.type,flat', 'numeric', 'min:0', 'max:1'],
            'role_id'              => ['nullable', 'exists:roles,id'],
            'department_id'        => ['nullable', 'exists:departments,id'],
        ]);

        if (empty($data['role_id']) && empty($data['department_id'])) {
            return $this->error('Indikator harus dihubungkan ke role atau departemen.', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $indicator = KpiIndicator::query()->create($data);
        $indicator->load(['role', 'department']);

        return $this->success($indicator, 'Indikator KPI berhasil dibuat.', Response::HTTP_CREATED);
    }

    /** GET /kpi-indicators/{indicator} */
    public function show(KpiIndicator $kpiIndicator): JsonResponse
    {
        $kpiIndicator->load(['role', 'department']);

        return $this->success($kpiIndicator);
    }

    /** PUT /kpi-indicators/{indicator} */
    public function update(Request $request, KpiIndicator $kpiIndicator): JsonResponse
    {
        $data = $request->validate([
            'name'                 => ['sometimes', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'weight'               => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'default_target_value' => ['nullable', 'numeric', 'min:0'],
            'formula'              => ['nullable', 'array'],
            'formula.type'         => ['required_with:formula', Rule::in(['percentage', 'conditional', 'threshold', 'zero_penalty', 'flat'])],
            'formula.thresholds'   => ['required_if:formula.type,threshold', 'array'],
            'formula.score'        => ['required_if:formula.type,flat', 'numeric', 'min:0', 'max:1'],
            'role_id'              => ['nullable', 'exists:roles,id'],
            'department_id'        => ['nullable', 'exists:departments,id'],
        ]);

        $kpiIndicator->update($data);
        $kpiIndicator->load(['role', 'department']);

        return $this->success($kpiIndicator, 'Indikator KPI berhasil diperbarui.');
    }

    /** DELETE /kpi-indicators/{indicator} */
    public function destroy(KpiIndicator $kpiIndicator): JsonResponse
    {
        $kpiIndicator->delete();

        return $this->success(null, 'Indikator KPI berhasil dihapus.');
    }

    /** GET /kpi-indicators/meta — departments + roles for form selects */
    public function meta(): JsonResponse
    {
        return $this->success([
            'departments' => Department::query()->where('is_active', true)->get(['id', 'nama', 'kode']),
            'roles'        => Role::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'formula_types' => [
                ['value' => 'percentage',   'label' => 'Persentase (aktual/target × bobot)'],
                ['value' => 'conditional',  'label' => 'Kondisional (penuh jika tercapai)'],
                ['value' => 'threshold',    'label' => 'Bertahap (skor per rentang %)'],
                ['value' => 'zero_penalty', 'label' => 'Zero Penalty (penuh jika nol pelanggaran)'],
                ['value' => 'flat',         'label' => 'Tetap (persentase tetap dari bobot)'],
            ],
        ]);
    }
}
