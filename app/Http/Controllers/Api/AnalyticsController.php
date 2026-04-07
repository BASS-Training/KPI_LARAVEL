<?php

namespace App\Http\Controllers\Api;

use App\Models\Division;
use App\Models\KpiReport;
use App\Models\User;
use App\Services\KpiCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends ApiController
{
    public function __construct(private KpiCalculatorService $kpiCalculator) {}

    /**
     * KPI trend per month for all employees or a specific division.
     * Returns data suitable for a line chart.
     */
    public function trend(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $divisionId = $request->input('division_id');

        $months = range(1, 12);
        $labels = collect($months)->map(fn ($m) => date('M', mktime(0, 0, 0, $m, 1)));

        $users = User::query()
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->where('role', 'pegawai')
            ->get();

        // Monthly averages from kpi_reports
        $reportData = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($divisionId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('division_id', $divisionId)))
            ->selectRaw('MONTH(tanggal) as bulan, AVG(persentase) as avg_persentase, COUNT(*) as jumlah')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $datasets = [];

        // Dataset 1: Report-based percentage average
        $reportPercentages = collect($months)->map(
            fn ($m) => $reportData->has($m) ? round((float) $reportData[$m]->avg_persentase, 1) : null
        );

        if ($reportPercentages->filter()->isNotEmpty()) {
            $datasets[] = [
                'label' => 'Rata-rata Pencapaian (%)',
                'data' => $reportPercentages->values()->all(),
                'type' => 'percentage',
            ];
        }

        // Dataset 2: Task-based KPI score average (0-5 converted to percentage)
        $taskScores = collect($months)->map(function ($m) use ($users, $tahun) {
            if ($users->isEmpty()) {
                return null;
            }

            $scores = $users->map(
                fn ($user) => $this->kpiCalculator->calculateForUser($user, $m, $tahun)['total']
            )->filter(fn ($s) => $s > 0);

            return $scores->isNotEmpty() ? round($scores->average(), 2) : null;
        });

        $datasets[] = [
            'label' => 'Rata-rata Skor KPI (0-5)',
            'data' => $taskScores->values()->all(),
            'type' => 'score',
        ];

        return $this->success([
            'labels' => $labels->values()->all(),
            'datasets' => $datasets,
            'tahun' => $tahun,
        ], 'Data trend berhasil dimuat');
    }

    /**
     * Average KPI achievement per division.
     * Returns data suitable for a bar chart.
     */
    public function perDivision(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->input('bulan');

        $divisions = Division::where('is_active', true)->get();

        $labels = $divisions->pluck('nama')->values()->all();

        // Percentage-based scores from kpi_reports
        $reportData = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->with('user:id,division_id')
            ->selectRaw('kpi_reports.user_id, AVG(persentase) as avg_persen')
            ->groupBy('kpi_reports.user_id')
            ->get()
            ->keyBy('user_id');

        $avgPercentages = $divisions->map(function ($division) use ($reportData) {
            $userIds = $division->users()->pluck('id');
            $scores = $userIds->map(fn ($uid) => $reportData->has($uid) ? (float) $reportData[$uid]->avg_persen : null)->filter();

            return $scores->isNotEmpty() ? round($scores->average(), 1) : 0;
        })->values()->all();

        // Task-based KPI scores
        $users = User::with('division')->where('role', 'pegawai')->get()->groupBy('division_id');

        $avgTaskScores = $divisions->map(function ($division) use ($users, $tahun, $bulan) {
            $divisionUsers = $users->get($division->id, collect());

            if ($divisionUsers->isEmpty()) {
                return 0;
            }

            $scores = $divisionUsers->map(
                fn ($user) => $this->kpiCalculator->calculateForUser($user, $bulan ? (int) $bulan : null, $tahun)['total']
            )->filter(fn ($s) => $s > 0);

            return $scores->isNotEmpty() ? round($scores->average(), 2) : 0;
        })->values()->all();

        return $this->success([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pencapaian KPI (%)',
                    'data' => $avgPercentages,
                    'type' => 'percentage',
                ],
                [
                    'label' => 'Skor KPI (0-5)',
                    'data' => $avgTaskScores,
                    'type' => 'score',
                ],
            ],
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], 'Data per divisi berhasil dimuat');
    }

    /**
     * Score distribution (Bad/Average/Good/Excellent).
     * Returns data suitable for a pie/doughnut chart.
     */
    public function distribution(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->input('bulan');
        $divisionId = $request->input('division_id');

        // Report-based distribution
        $reportCounts = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($divisionId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('division_id', $divisionId)))
            ->whereNotNull('score_label')
            ->selectRaw('score_label, COUNT(*) as jumlah')
            ->groupBy('score_label')
            ->pluck('jumlah', 'score_label')
            ->toArray();

        // Task-based distribution
        $userQuery = User::where('role', 'pegawai')
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId));

        $taskDistribution = ['Baik Sekali' => 0, 'Baik' => 0, 'Cukup' => 0, 'Kurang' => 0, 'Buruk' => 0];

        foreach ($userQuery->get() as $user) {
            $result = $this->kpiCalculator->calculateForUser($user, $bulan ? (int) $bulan : null, $tahun);
            $predikat = $result['predikat'];
            if (isset($taskDistribution[$predikat])) {
                $taskDistribution[$predikat]++;
            }
        }

        return $this->success([
            'report_based' => [
                'labels' => ['Excellent (>100%)', 'Good (80-100%)', 'Average (50-80%)', 'Bad (<50%)'],
                'data' => [
                    $reportCounts['excellent'] ?? 0,
                    $reportCounts['good'] ?? 0,
                    $reportCounts['average'] ?? 0,
                    $reportCounts['bad'] ?? 0,
                ],
            ],
            'task_based' => [
                'labels' => array_keys($taskDistribution),
                'data' => array_values($taskDistribution),
            ],
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], 'Data distribusi berhasil dimuat');
    }

    /**
     * Overview stats for a quick summary.
     */
    public function overview(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);

        $totalEmployees = User::where('role', 'pegawai')->count();

        $reportStats = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->selectRaw('AVG(persentase) as avg_persen, COUNT(*) as total_reports,
                SUM(CASE WHEN score_label = "excellent" THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN score_label = "good" THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN score_label = "average" THEN 1 ELSE 0 END) as average_count,
                SUM(CASE WHEN score_label = "bad" THEN 1 ELSE 0 END) as bad')
            ->first();

        $totalDivisions = Division::where('is_active', true)->count();

        return $this->success([
            'total_employees' => $totalEmployees,
            'total_divisions' => $totalDivisions,
            'total_reports' => (int) ($reportStats->total_reports ?? 0),
            'avg_achievement' => round((float) ($reportStats->avg_persen ?? 0), 1),
            'excellent_count' => (int) ($reportStats->excellent ?? 0),
            'good_count' => (int) ($reportStats->good ?? 0),
            'average_count' => (int) ($reportStats->average_count ?? 0),
            'bad_count' => (int) ($reportStats->bad ?? 0),
            'bulan' => $bulan,
            'tahun' => $tahun,
        ], 'Ringkasan analytics berhasil dimuat');
    }
}
