<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreKpiReportRequest;
use App\Http\Resources\KpiReportResource;
use App\Models\ActivityLog;
use App\Models\KpiComponent;
use App\Models\KpiReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KpiReportController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = KpiReport::with(['user', 'kpiComponent'])
            ->when(! $user->canManageAllData(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->filled('user_id') && $user->canManageAllData(), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('kpi_component_id'), fn ($q) => $q->where('kpi_component_id', $request->kpi_component_id))
            ->when($request->filled('bulan'), fn ($q) => $q->whereMonth('tanggal', $request->bulan))
            ->when($request->filled('tahun'), fn ($q) => $q->whereYear('tanggal', $request->tahun))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('tanggal');

        $perPage = (int) $request->input('per_page', 20);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            KpiReportResource::collection($paginator->getCollection()),
            $paginator
        );
    }

    public function store(StoreKpiReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Pegawai can only report their own KPI
        if (! $user->canManageAllData()) {
            $data['user_id'] = $user->id;
        } else {
            $data['user_id'] = $data['user_id'] ?? $user->id;
        }

        // Compute percentage from component target if not provided
        $component = KpiComponent::find($data['kpi_component_id']);
        $nilaiTarget = $data['nilai_target'] ?? ($component?->target ?? null);
        $data['nilai_target'] = $nilaiTarget;

        if ($nilaiTarget && $nilaiTarget > 0) {
            $data['persentase'] = round((float) $data['nilai_aktual'] / (float) $nilaiTarget * 100, 2);
        } else {
            $data['persentase'] = (float) $data['nilai_aktual'] > 0 ? 100.0 : 0.0;
        }

        $data['score_label'] = KpiReport::resolveScoreLabel((float) ($data['persentase'] ?? 0));

        if (($data['status'] ?? 'draft') === 'submitted') {
            $data['submitted_by'] = $user->id;
            $data['submitted_at'] = now();
        }

        $report = KpiReport::create($data);

        ActivityLog::record($user, 'create_kpi_report', 'KpiReport', $report->id, [], $request);

        return $this->resource(new KpiReportResource($report->load(['user', 'kpiComponent'])), 'Laporan KPI berhasil disimpan', 201);
    }

    public function update(StoreKpiReportRequest $request, KpiReport $kpiReport): JsonResponse
    {
        $user = $request->user();

        // Only the author or HR/Direktur can edit
        if (! $user->canManageAllData() && $kpiReport->user_id !== $user->id) {
            return $this->error('Anda tidak memiliki akses untuk mengubah laporan ini.', [], 403);
        }

        $data = $request->validated();

        $nilaiTarget = $data['nilai_target'] ?? $kpiReport->nilai_target;
        $nilaiAktual = $data['nilai_aktual'] ?? $kpiReport->nilai_aktual;

        if ($nilaiTarget && $nilaiTarget > 0) {
            $data['persentase'] = round((float) $nilaiAktual / (float) $nilaiTarget * 100, 2);
        }

        $data['score_label'] = KpiReport::resolveScoreLabel((float) ($data['persentase'] ?? $kpiReport->persentase ?? 0));

        if (($data['status'] ?? $kpiReport->status) === 'submitted' && ! $kpiReport->submitted_at) {
            $data['submitted_by'] = $user->id;
            $data['submitted_at'] = now();
        }

        $kpiReport->update($data);

        ActivityLog::record($user, 'update_kpi_report', 'KpiReport', $kpiReport->id, [], $request);

        return $this->resource(new KpiReportResource($kpiReport->load(['user', 'kpiComponent'])), 'Laporan KPI berhasil diperbarui');
    }

    public function destroy(Request $request, KpiReport $kpiReport): JsonResponse
    {
        $user = $request->user();

        if (! $user->canManageAllData() && $kpiReport->user_id !== $user->id) {
            return $this->error('Anda tidak memiliki akses untuk menghapus laporan ini.', [], 403);
        }

        // Clean up evidence file if exists
        if ($kpiReport->file_evidence) {
            Storage::disk('public')->delete($kpiReport->file_evidence);
        }

        $kpiReport->delete();

        ActivityLog::record($user, 'delete_kpi_report', 'KpiReport', null, ['id' => $kpiReport->id], $request);

        return $this->success(null, 'Laporan KPI berhasil dihapus');
    }

    public function uploadEvidence(Request $request, KpiReport $kpiReport): JsonResponse
    {
        $user = $request->user();

        if (! $user->canManageAllData() && $kpiReport->user_id !== $user->id) {
            return $this->error('Akses ditolak.', [], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,png,jpg,jpeg,doc,docx,xlsx'],
        ]);

        // Delete old file
        if ($kpiReport->file_evidence) {
            Storage::disk('public')->delete($kpiReport->file_evidence);
        }

        $path = $request->file('file')->store(
            'kpi-evidence/'.date('Y').'/'.date('m'),
            'public'
        );

        $kpiReport->update(['file_evidence' => $path]);

        return $this->success([
            'file_evidence' => $path,
            'file_evidence_url' => $kpiReport->file_evidence_url,
        ], 'File evidence berhasil diunggah');
    }
}
