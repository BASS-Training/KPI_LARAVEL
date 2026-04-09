<?php

namespace App\Http\Controllers\Api;

use App\Models\KpiReport;
use App\Models\User;
use App\Services\KpiCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends ApiController
{
    public function __construct(private KpiCalculatorService $kpiCalculator) {}

    /**
     * Export individual KPI report to PDF.
     */
    public function kpiPdf(Request $request, User $user): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $kpiData = $this->kpiCalculator->calculateForUser($user, $bulan, $tahun);

        $reports = KpiReport::query()
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->with('kpiComponent')
            ->get();

        $bulanLabel = \DateTime::createFromFormat('!m', $bulan)->format('F');

        $pdf = Pdf::loadView('exports.kpi_report', [
            'user' => $user,
            'kpiData' => $kpiData,
            'reports' => $reports,
            'bulan' => $bulanLabel,
            'tahun' => $tahun,
            'generatedAt' => now()->format('d M Y H:i'),
        ]);

        $filename = "KPI_{$user->nip}_{$bulan}_{$tahun}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Export ranking to simple CSV (Excel-compatible, no ext-gd required).
     */
    public function rankingCsv(Request $request): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $ranking = $this->kpiCalculator->ranking($bulan, $tahun);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Ranking_KPI_{$bulan}_{$tahun}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($ranking) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Rank', 'NIP', 'Nama', 'Jabatan', 'Departemen', 'Divisi', 'Skor KPI', 'Predikat']);

            foreach ($ranking as $index => $item) {
                fputcsv($handle, [
                    $index + 1,
                    $item['user']->nip,
                    $item['user']->nama,
                    $item['user']->jabatan,
                    $item['user']->departemen,
                    $item['user']->division?->nama ?? '-',
                    $item['total'],
                    $item['predikat'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export KPI reports to CSV.
     */
    public function reportsCsv(Request $request): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $divisionId = $request->input('division_id');

        $reports = KpiReport::query()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->when($divisionId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('division_id', $divisionId)))
            ->with(['user.division', 'kpiComponent'])
            ->orderByDesc('persentase')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Laporan_KPI_{$bulan}_{$tahun}.csv\"",
        ];

        $callback = function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['NIP', 'Nama', 'Divisi', 'Komponen KPI', 'Target', 'Aktual', 'Persentase (%)', 'Predikat', 'Tanggal', 'Status']);

            foreach ($reports as $r) {
                fputcsv($handle, [
                    $r->user?->nip ?? '-',
                    $r->user?->nama ?? '-',
                    $r->user?->division?->nama ?? '-',
                    $r->kpiComponent?->objectives ?? '-',
                    $r->nilai_target ?? '-',
                    $r->nilai_aktual ?? '-',
                    $r->persentase ?? '-',
                    match ($r->score_label) {
                        'excellent' => 'Excellent (>100%)',
                        'good' => 'Good (80-100%)',
                        'average' => 'Average (50-80%)',
                        'bad' => 'Bad (<50%)',
                        default => '-',
                    },
                    $r->tanggal?->toDateString() ?? '-',
                    $r->status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
