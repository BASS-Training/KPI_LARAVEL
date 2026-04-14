<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\Role;
use App\Services\KpiFormulaEngine;
use Illuminate\Database\Seeder;

/**
 * Seeds KPI Indicators keyed to departments.
 * Each indicator uses a formula JSON matching the company's sasaran mutu.
 */
class KpiIndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $depts = Department::query()->pluck('id', 'kode');
        $defaultThresholds = KpiFormulaEngine::defaultThresholds();

        $indicators = [
            // ── Board of Director ─────────────────────────────────────────
            'BOD' => [
                [
                    'name'                 => 'Strategic Goals Achievement',
                    'description'          => 'Persentase pencapaian target strategis tahunan yang telah ditetapkan dewan direksi.',
                    'weight'               => 50.00,
                    'default_target_value' => 100,
                    'formula'              => ['type' => 'threshold', 'thresholds' => $defaultThresholds],
                ],
                [
                    'name'                 => 'Board Decisions Implementation Rate',
                    'description'          => 'Tingkat implementasi keputusan rapat direksi dalam waktu yang ditentukan.',
                    'weight'               => 30.00,
                    'default_target_value' => 100,
                    'formula'              => ['type' => 'conditional'],
                ],
                [
                    'name'                 => 'Stakeholder Satisfaction Score',
                    'description'          => 'Indeks kepuasan pemangku kepentingan utama berdasarkan survei tahunan.',
                    'weight'               => 20.00,
                    'default_target_value' => 80,
                    'formula'              => ['type' => 'threshold', 'thresholds' => $defaultThresholds],
                ],
            ],

            // ── Finance & Accounting ──────────────────────────────────────
            'FNA' => [
                [
                    'name'                 => 'Invoice Accuracy Rate',
                    'description'          => 'Tidak ada kesalahan nominal atau data pajak dalam pembuatan invoice.',
                    'weight'               => 35.00,
                    'default_target_value' => 0,
                    'formula'              => ['type' => 'zero_penalty'],
                ],
                [
                    'name'                 => 'Financial Report Timeliness',
                    'description'          => 'Laporan keuangan bulanan dikirim sebelum tanggal 5.',
                    'weight'               => 30.00,
                    'default_target_value' => 100,
                    'formula'              => ['type' => 'threshold', 'thresholds' => $defaultThresholds],
                ],
                [
                    'name'                 => 'Budget Variance',
                    'description'          => 'Selisih antara anggaran yang direncanakan dan realisasi pengeluaran.',
                    'weight'               => 35.00,
                    'default_target_value' => 95,
                    'formula'              => ['type' => 'conditional'],
                ],
            ],

            // ── HR & GA ───────────────────────────────────────────────────
            'HGA' => [
                [
                    'name'                 => 'Recruitment Fulfillment Rate',
                    'description'          => 'Persentase posisi yang berhasil diisi dalam target waktu rekrutmen.',
                    'weight'               => 35.00,
                    'default_target_value' => 100,
                    'formula'              => ['type' => 'percentage'],
                ],
                [
                    'name'                 => 'Employee Satisfaction Index',
                    'description'          => 'Skor kepuasan karyawan dari survei internal bulanan.',
                    'weight'               => 30.00,
                    'default_target_value' => 80,
                    'formula'              => ['type' => 'threshold', 'thresholds' => $defaultThresholds],
                ],
                [
                    'name'                 => 'Training Completion Rate',
                    'description'          => 'Persentase program pelatihan yang terlaksana sesuai jadwal.',
                    'weight'               => 35.00,
                    'default_target_value' => 100,
                    'formula'              => ['type' => 'threshold', 'thresholds' => $defaultThresholds],
                ],
            ],

            // ── Business Development ──────────────────────────────────────
            'BDV' => [
                [
                    'name'                 => 'Sales Revenue Achievement',
                    'description'          => 'Realisasi pendapatan penjualan dibanding target bulanan.',
                    'weight'               => 50.00,
                    'default_target_value' => 3500000000,
                    'formula'              => ['type' => 'conditional'],
                ],
                [
                    'name'                 => 'New Client Acquisition',
                    'description'          => 'Jumlah klien baru yang berhasil diakuisisi dalam periode.',
                    'weight'               => 30.00,
                    'default_target_value' => 5,
                    'formula'              => ['type' => 'percentage'],
                ],
                [
                    'name'                 => 'Customer Satisfaction Index (CSI)',
                    'description'          => 'Rata-rata skor kepuasan pelanggan dari feedback post-service.',
                    'weight'               => 20.00,
                    'default_target_value' => 3.5,
                    'formula'              => ['type' => 'threshold', 'thresholds' => [
                        ['min_pct' => 100, 'score_pct' => 100],
                        ['min_pct' => 90,  'score_pct' => 80],
                        ['min_pct' => 80,  'score_pct' => 60],
                        ['min_pct' => 70,  'score_pct' => 40],
                        ['min_pct' => 0,   'score_pct' => 0],
                    ]],
                ],
                [
                    'name'                 => 'Zero Delay Proposal',
                    'description'          => 'Tidak ada keterlambatan pengiriman proposal dari SLA yang ditentukan.',
                    'weight'               => 0.00,  // tidak dihitung terpisah, masuk ke bobot lain
                    'default_target_value' => 0,
                    'formula'              => ['type' => 'zero_penalty'],
                ],
            ],

            // ── Research & Development ────────────────────────────────────
            'RND' => [
                [
                    'name'                 => 'Project Delivery Rate',
                    'description'          => 'Persentase proyek R&D yang selesai tepat waktu.',
                    'weight'               => 40.00,
                    'default_target_value' => 100,
                    'formula'              => ['type' => 'threshold', 'thresholds' => $defaultThresholds],
                ],
                [
                    'name'                 => 'Innovation Proposals Submitted',
                    'description'          => 'Jumlah proposal inovasi yang diajukan ke manajemen per periode.',
                    'weight'               => 30.00,
                    'default_target_value' => 2,
                    'formula'              => ['type' => 'percentage'],
                ],
                [
                    'name'                 => 'Technical Documentation Completeness',
                    'description'          => 'Semua dokumen teknis proyek tersedia dan up-to-date.',
                    'weight'               => 30.00,
                    'default_target_value' => 0,
                    'formula'              => ['type' => 'zero_penalty'],
                ],
            ],
        ];

        foreach ($indicators as $deptKode => $items) {
            $departmentId = $depts[$deptKode] ?? null;

            if (! $departmentId) {
                $this->command->warn("Department {$deptKode} not found, skipping.");
                continue;
            }

            foreach ($items as $item) {
                KpiIndicator::query()->updateOrCreate(
                    [
                        'name'          => $item['name'],
                        'department_id' => $departmentId,
                    ],
                    [
                        'description'          => $item['description'],
                        'weight'               => $item['weight'],
                        'default_target_value' => $item['default_target_value'],
                        'formula'              => $item['formula'],
                        'role_id'              => null, // department-based, not role-based
                    ]
                );
            }
        }

        $this->command->info('KPI Indicators seeded: ' . KpiIndicator::query()->whereNotNull('department_id')->count() . ' department-based indicators.');
    }
}
