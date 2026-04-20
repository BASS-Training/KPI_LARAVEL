<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\Position;
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

    /**
     * @OA\Get(path="/kpi-indicators", tags={"KPI Indicator"}, summary="Daftar indikator KPI",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="department_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $indicators = KpiIndicator::query()
            ->with(['department', 'position'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->orderBy('department_id')
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
                'department_id'        => $ind->department_id,
                'department'           => $ind->department ? ['id' => $ind->department->id, 'nama' => $ind->department->nama] : null,
                'position_id'          => $ind->position_id,
                'position'             => $ind->position ? ['id' => $ind->position->id, 'nama' => $ind->position->nama] : null,
            ]);

        return $this->success(['items' => $indicators]);
    }

    /**
     * @OA\Post(path="/kpi-indicators", tags={"KPI Indicator"}, summary="Buat indikator KPI",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name","weight"},
     *         @OA\Property(property="name", type="string", example="Ketepatan Laporan"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="weight", type="number", example=20),
     *         @OA\Property(property="default_target_value", type="number", example=100),
     *         @OA\Property(property="department_id", type="integer", example=1),
     *         @OA\Property(property="position_id", type="integer", example=null),
     *         @OA\Property(property="formula", type="object")
     *     )),
     *     @OA\Response(response=201, description="Indikator berhasil dibuat"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
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
            'department_id'        => ['nullable', 'exists:departments,id'],
            'position_id'          => ['nullable', 'exists:positions,id'],
        ]);

        if (empty($data['department_id']) && empty($data['position_id'])) {
            return $this->error('Pilih departemen atau jabatan.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $indicator = KpiIndicator::query()->create($data);
        $indicator->load(['department', 'position']);

        return $this->success($indicator, 'Indikator KPI berhasil dibuat.', Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(path="/kpi-indicators/{id}", tags={"KPI Indicator"}, summary="Detail indikator KPI",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function show(KpiIndicator $kpiIndicator): JsonResponse
    {
        $kpiIndicator->load(['department']);

        return $this->success($kpiIndicator);
    }

    /**
     * @OA\Put(path="/kpi-indicators/{id}", tags={"KPI Indicator"}, summary="Update indikator KPI",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="weight", type="number"),
     *         @OA\Property(property="department_id", type="integer"),
     *         @OA\Property(property="position_id", type="integer")
     *     )),
     *     @OA\Response(response=200, description="OK")
     * )
     */
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
            'department_id'        => ['nullable', 'exists:departments,id'],
            'position_id'          => ['nullable', 'exists:positions,id'],
        ]);

        $kpiIndicator->update($data);
        $kpiIndicator->load(['department', 'position']);

        return $this->success($kpiIndicator, 'Indikator KPI berhasil diperbarui.');
    }

    /**
     * @OA\Delete(path="/kpi-indicators/{id}", tags={"KPI Indicator"}, summary="Hapus indikator KPI",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function destroy(KpiIndicator $kpiIndicator): JsonResponse
    {
        $kpiIndicator->delete();

        return $this->success(null, 'Indikator KPI berhasil dihapus.');
    }

    /** GET /kpi-indicators/meta — departments + Spatie roles for form selects */
    public function meta(): JsonResponse
    {
        return $this->success([
            'departments' => Department::query()->where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'kode']),
            'positions'   => Position::query()->where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'kode', 'department_id']),
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
