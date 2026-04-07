<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\KpiComponent;
use App\Models\User;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['nama' => 'IT', 'kode' => 'IT', 'deskripsi' => 'Information Technology — Pengembangan sistem, infrastruktur, dan support teknis'],
            ['nama' => 'Sales', 'kode' => 'SALES', 'deskripsi' => 'Sales — Penjualan produk dan akuisisi klien baru'],
            ['nama' => 'Marketing', 'kode' => 'MKT', 'deskripsi' => 'Marketing — Strategi pemasaran, branding, dan komunikasi'],
            ['nama' => 'Digital Promotion', 'kode' => 'DIGPRO', 'deskripsi' => 'Digital Promotion — Promosi digital, media sosial, dan iklan online'],
            ['nama' => 'R&D', 'kode' => 'RND', 'deskripsi' => 'Research & Development — Riset dan pengembangan produk serta kurikulum'],
            ['nama' => 'Data Analyst', 'kode' => 'DATA', 'deskripsi' => 'Data Analyst — Analisis data bisnis dan pelaporan strategis'],
            ['nama' => 'Finance', 'kode' => 'FIN', 'deskripsi' => 'Finance & Accounting — Pengelolaan keuangan dan pelaporan'],
            ['nama' => 'HR & GA', 'kode' => 'HRGA', 'deskripsi' => 'HR & General Affairs — SDM dan administrasi umum'],
        ];

        foreach ($divisions as $division) {
            Division::updateOrCreate(['kode' => $division['kode']], $division + ['is_active' => true]);
        }

        // Map existing users to divisions
        $divisionMap = Division::pluck('id', 'kode')->toArray();

        $userDivisionMapping = [
            'Marketing & Sales' => $divisionMap['MKT'] ?? null,
            'Finance' => $divisionMap['FIN'] ?? null,
            'HR & GA Manager' => $divisionMap['HRGA'] ?? null,
            'Direktur Utama' => null,
            'Direktur' => null,
        ];

        foreach ($userDivisionMapping as $jabatan => $divisionId) {
            if ($divisionId) {
                User::where('jabatan', $jabatan)->update(['division_id' => $divisionId]);
            }
        }

        // Map existing kpi_components to divisions
        $componentDivisionMapping = [
            'Marketing & Sales' => $divisionMap['MKT'] ?? null,
            'Finance' => $divisionMap['FIN'] ?? null,
        ];

        foreach ($componentDivisionMapping as $jabatan => $divisionId) {
            if ($divisionId) {
                KpiComponent::where('jabatan', $jabatan)->update(['division_id' => $divisionId]);
            }
        }

        // Add demo KPI components for new divisions
        $this->seedKpiComponents($divisionMap);
    }

    private function seedKpiComponents(array $divisionMap): void
    {
        $components = [
            // IT Division
            [
                'jabatan' => 'IT Staff',
                'division_id' => $divisionMap['IT'] ?? null,
                'objectives' => 'System Uptime',
                'strategy' => 'Menjaga ketersediaan sistem 99.5% dalam sebulan',
                'bobot' => 0.40,
                'target' => 99.5,
                'satuan' => '%',
                'tipe' => 'achievement',
                'kpi_type' => 'percentage',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'IT Staff',
                'division_id' => $divisionMap['IT'] ?? null,
                'objectives' => 'Ticket Resolution Rate',
                'strategy' => 'Menyelesaikan minimal 95% tiket support dalam SLA',
                'bobot' => 0.35,
                'target' => 95,
                'satuan' => '%',
                'tipe' => 'achievement',
                'kpi_type' => 'percentage',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'IT Staff',
                'division_id' => $divisionMap['IT'] ?? null,
                'objectives' => 'Zero Critical Bug in Production',
                'strategy' => 'Tidak ada bug kritis lolos ke environment produksi',
                'bobot' => 0.25,
                'target' => null,
                'tipe' => 'zero_error',
                'kpi_type' => 'boolean',
                'period' => 'monthly',
                'is_active' => true,
            ],
            // Sales Division
            [
                'jabatan' => 'Sales Executive',
                'division_id' => $divisionMap['SALES'] ?? null,
                'objectives' => 'Revenue Target',
                'strategy' => 'Mencapai target penjualan bulanan',
                'bobot' => 0.60,
                'target' => 5000000000,
                'satuan' => 'Rp',
                'tipe' => 'achievement',
                'kpi_type' => 'number',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Sales Executive',
                'division_id' => $divisionMap['SALES'] ?? null,
                'objectives' => 'New Client Acquisition',
                'strategy' => 'Akuisisi minimal 5 klien baru per bulan',
                'bobot' => 0.25,
                'target' => 5,
                'satuan' => 'klien',
                'tipe' => 'achievement',
                'kpi_type' => 'number',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Sales Executive',
                'division_id' => $divisionMap['SALES'] ?? null,
                'objectives' => 'Zero Customer Complaint',
                'strategy' => 'Tidak ada keluhan pelanggan terkait proses penjualan',
                'bobot' => 0.15,
                'target' => null,
                'tipe' => 'zero_complaint',
                'kpi_type' => 'boolean',
                'period' => 'monthly',
                'is_active' => true,
            ],
            // Digital Promotion
            [
                'jabatan' => 'Digital Marketing Specialist',
                'division_id' => $divisionMap['DIGPRO'] ?? null,
                'objectives' => 'Social Media Engagement Rate',
                'strategy' => 'Mencapai engagement rate minimal 4% di semua platform',
                'bobot' => 0.40,
                'target' => 4,
                'satuan' => '%',
                'tipe' => 'achievement',
                'kpi_type' => 'percentage',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Digital Marketing Specialist',
                'division_id' => $divisionMap['DIGPRO'] ?? null,
                'objectives' => 'Lead Generation',
                'strategy' => 'Menghasilkan minimal 100 qualified leads per bulan',
                'bobot' => 0.35,
                'target' => 100,
                'satuan' => 'leads',
                'tipe' => 'achievement',
                'kpi_type' => 'number',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Digital Marketing Specialist',
                'division_id' => $divisionMap['DIGPRO'] ?? null,
                'objectives' => 'Content Publish On-Time',
                'strategy' => 'Semua konten dipublish tepat waktu sesuai jadwal',
                'bobot' => 0.25,
                'target' => null,
                'tipe' => 'zero_delay',
                'kpi_type' => 'boolean',
                'period' => 'monthly',
                'is_active' => true,
            ],
            // R&D
            [
                'jabatan' => 'Research Analyst',
                'division_id' => $divisionMap['RND'] ?? null,
                'objectives' => 'Research Output',
                'strategy' => 'Menyelesaikan minimal 2 riset / laporan per bulan',
                'bobot' => 0.50,
                'target' => 2,
                'satuan' => 'laporan',
                'tipe' => 'achievement',
                'kpi_type' => 'number',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Research Analyst',
                'division_id' => $divisionMap['RND'] ?? null,
                'objectives' => 'Curriculum Accuracy',
                'strategy' => 'Akurasi dan relevansi kurikulum yang dikembangkan',
                'bobot' => 0.50,
                'target' => 90,
                'satuan' => '%',
                'tipe' => 'achievement',
                'kpi_type' => 'percentage',
                'period' => 'monthly',
                'is_active' => true,
            ],
            // Data Analyst
            [
                'jabatan' => 'Data Analyst',
                'division_id' => $divisionMap['DATA'] ?? null,
                'objectives' => 'Report Delivery On-Time',
                'strategy' => 'Semua laporan analitik disampaikan tepat deadline',
                'bobot' => 0.40,
                'target' => null,
                'tipe' => 'zero_delay',
                'kpi_type' => 'boolean',
                'period' => 'monthly',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Data Analyst',
                'division_id' => $divisionMap['DATA'] ?? null,
                'objectives' => 'Data Accuracy Rate',
                'strategy' => 'Akurasi data yang diolah minimal 98%',
                'bobot' => 0.60,
                'target' => 98,
                'satuan' => '%',
                'tipe' => 'achievement',
                'kpi_type' => 'percentage',
                'period' => 'monthly',
                'is_active' => true,
            ],
        ];

        foreach ($components as $comp) {
            KpiComponent::updateOrCreate(
                ['jabatan' => $comp['jabatan'], 'objectives' => $comp['objectives']],
                $comp
            );
        }
    }
}
