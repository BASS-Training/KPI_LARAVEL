<?php

namespace App\Console\Commands;

use App\Services\KpiService;
use Illuminate\Console\Command;

class GenerateMonthlyKpiCommand extends Command
{
    protected $signature = 'kpi:generate-monthly {--period=}';

    protected $description = 'Generate KPI bulanan untuk semua user aktif berdasarkan role.';

    public function handle(KpiService $kpiService): int
    {
        $count = $kpiService->generateMonthlyKPI($this->option('period'));

        $this->info("KPI bulanan berhasil digenerate untuk {$count} user.");

        return self::SUCCESS;
    }
}
