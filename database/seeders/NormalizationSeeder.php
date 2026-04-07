<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\KpiComponent;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * NormalizationSeeder
 *
 * Backfills departments, positions tables from existing string data,
 * then updates FKs on users, kpi_components, sla.
 *
 * Safe to run multiple times (uses updateOrCreate).
 */
class NormalizationSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. SEED DEPARTMENTS ────────────────────────────────────────────
        $divisionMap = Division::pluck('id', 'kode')->toArray();

        $departments = [
            ['nama' => 'Board of Director', 'kode' => 'BOD',      'division_id' => null],
            ['nama' => 'HR & GA',            'kode' => 'HRGA_DEP', 'division_id' => $divisionMap['HRGA'] ?? null],
            ['nama' => 'Marketing',          'kode' => 'MKT_DEP',  'division_id' => $divisionMap['MKT'] ?? null],
            ['nama' => 'Finance',            'kode' => 'FIN_DEP',  'division_id' => $divisionMap['FIN'] ?? null],
            ['nama' => 'IT Department',      'kode' => 'IT_DEP',   'division_id' => $divisionMap['IT'] ?? null],
            ['nama' => 'Sales',              'kode' => 'SALES_DEP','division_id' => $divisionMap['SALES'] ?? null],
            ['nama' => 'Digital Promotion',  'kode' => 'DIGPRO_DEP','division_id' => $divisionMap['DIGPRO'] ?? null],
            ['nama' => 'R&D',                'kode' => 'RND_DEP',  'division_id' => $divisionMap['RND'] ?? null],
            ['nama' => 'Data Analyst',       'kode' => 'DATA_DEP', 'division_id' => $divisionMap['DATA'] ?? null],
            ['nama' => 'Accounting',         'kode' => 'ACC_DEP',  'division_id' => $divisionMap['FIN'] ?? null],
        ];

        $deptMap = [];
        foreach ($departments as $dept) {
            $record = DB::table('departments')->updateOrInsert(
                ['kode' => $dept['kode']],
                array_merge($dept, ['is_active' => true, 'updated_at' => now(), 'created_at' => now()])
            );
            $deptMap[$dept['kode']] = DB::table('departments')->where('kode', $dept['kode'])->value('id');
        }

        // ─── 2. SEED POSITIONS ─────────────────────────────────────────────
        $positions = [
            // Executives (BOD)
            ['nama' => 'Direktur Utama',              'kode' => 'DIRUT',       'department_id' => $deptMap['BOD'],      'level' => 'executive'],
            ['nama' => 'Direktur',                    'kode' => 'DIR',         'department_id' => $deptMap['BOD'],      'level' => 'director'],

            // HR & GA
            ['nama' => 'HR & GA Manager',             'kode' => 'HR_MGR',      'department_id' => $deptMap['HRGA_DEP'], 'level' => 'manager'],

            // Marketing & Sales
            ['nama' => 'Marketing & Sales',           'kode' => 'MKT_SALES',   'department_id' => $deptMap['MKT_DEP'],  'level' => 'staff'],
            ['nama' => 'Sales Executive',             'kode' => 'SALES_EXEC',  'department_id' => $deptMap['SALES_DEP'],'level' => 'staff'],
            ['nama' => 'Digital Marketing Specialist','kode' => 'DIGPRO_SPEC', 'department_id' => $deptMap['DIGPRO_DEP'],'level' => 'staff'],

            // Finance & Accounting
            ['nama' => 'Finance',                     'kode' => 'FINANCE',     'department_id' => $deptMap['FIN_DEP'],  'level' => 'staff'],
            ['nama' => 'Accounting',                  'kode' => 'ACCOUNTING',  'department_id' => $deptMap['ACC_DEP'],  'level' => 'staff'],

            // IT
            ['nama' => 'IT Staff',                    'kode' => 'IT_STAFF',    'department_id' => $deptMap['IT_DEP'],   'level' => 'staff'],

            // R&D
            ['nama' => 'Research Analyst',            'kode' => 'RND_ANALYST', 'department_id' => $deptMap['RND_DEP'],  'level' => 'staff'],

            // Data
            ['nama' => 'Data Analyst',                'kode' => 'DATA_ANALYST','department_id' => $deptMap['DATA_DEP'], 'level' => 'staff'],
        ];

        $posMap = [];
        foreach ($positions as $pos) {
            DB::table('positions')->updateOrInsert(
                ['kode' => $pos['kode']],
                array_merge($pos, ['is_active' => true, 'updated_at' => now(), 'created_at' => now()])
            );
            $posMap[$pos['kode']] = DB::table('positions')->where('kode', $pos['kode'])->value('id');
        }

        // ─── 3. BACKFILL users (department_id + position_id) ───────────────
        $jabatanToPosition = [
            'Direktur Utama'   => 'DIRUT',
            'Direktur'         => 'DIR',
            'HR & GA Manager'  => 'HR_MGR',
            'Marketing & Sales'=> 'MKT_SALES',
            'Finance'          => 'FINANCE',
        ];

        $departemenToDept = [
            'Board of Director' => 'BOD',
            'HR & GA'           => 'HRGA_DEP',
            'Marketing'         => 'MKT_DEP',
            'Finance'           => 'FIN_DEP',
        ];

        foreach (User::all() as $user) {
            $updates = [];

            $posKode = $jabatanToPosition[$user->jabatan] ?? null;
            if ($posKode && isset($posMap[$posKode])) {
                $updates['position_id'] = $posMap[$posKode];
            }

            $deptKode = $departemenToDept[$user->departemen] ?? null;
            if ($deptKode && isset($deptMap[$deptKode])) {
                $updates['department_id'] = $deptMap[$deptKode];
            }

            if ($updates) {
                $user->update($updates);
            }
        }

        // ─── 4. BACKFILL kpi_components (position_id) ──────────────────────
        $kpiJabatanToPos = [
            'Marketing & Sales'            => 'MKT_SALES',
            'Finance'                      => 'FINANCE',
            'IT Staff'                     => 'IT_STAFF',
            'IT'                           => 'IT_STAFF',
            'Sales Executive'              => 'SALES_EXEC',
            'Digital Marketing Specialist' => 'DIGPRO_SPEC',
            'Research Analyst'             => 'RND_ANALYST',
            'Data Analyst'                 => 'DATA_ANALYST',
        ];

        foreach (KpiComponent::all() as $comp) {
            $posKode = $kpiJabatanToPos[$comp->jabatan] ?? null;
            if ($posKode && isset($posMap[$posKode])) {
                $comp->update(['position_id' => $posMap[$posKode]]);
            }
        }

        // ─── 5. BACKFILL sla (position_id) ─────────────────────────────────
        $slaJabatanToPos = [
            'Finance'           => 'FINANCE',
            'Accounting'        => 'ACCOUNTING',
            'Marketing & Sales' => 'MKT_SALES',
        ];

        foreach (Sla::all() as $sla) {
            $posKode = $slaJabatanToPos[$sla->jabatan] ?? null;
            if ($posKode && isset($posMap[$posKode])) {
                $sla->update(['position_id' => $posMap[$posKode]]);
            }
        }

        $this->command->info('Normalization complete: '.count($departments).' departments, '.count($positions).' positions backfilled.');
    }
}
