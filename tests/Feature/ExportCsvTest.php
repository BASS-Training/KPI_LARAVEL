<?php

namespace Tests\Feature;

use App\Models\KpiComponent;
use App\Models\KpiReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_export_ranking_csv(): void
    {
        $hr = User::query()->create([
            'nip' => 'HR-001',
            'nama' => 'HR Manager',
            'jabatan' => 'HR Manager',
            'departemen' => 'HR',
            'status_karyawan' => 'tetap',
            'tanggal_masuk' => '2026-01-01',
            'email' => 'hr@example.com',
            'role' => 'hr_manager',
            'password' => bcrypt('password'),
        ]);

        User::query()->create([
            'nip' => 'PG-001',
            'nama' => 'Pegawai Satu',
            'jabatan' => 'Staff',
            'departemen' => 'Operasional',
            'status_karyawan' => 'tetap',
            'tanggal_masuk' => '2026-01-01',
            'email' => 'pegawai1@example.com',
            'role' => 'pegawai',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($hr);

        $response = $this->get('/api/export/ranking/csv?tahun=2026&bulan=4');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="Ranking_KPI_4_2026.csv"');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Rank,NIP,Nama,Jabatan,Departemen,Divisi,"Skor KPI",Predikat', $content);
        $this->assertStringContainsString('PG-001', $content);
    }

    public function test_hr_can_export_reports_csv(): void
    {
        $hr = User::query()->create([
            'nip' => 'HR-001',
            'nama' => 'HR Manager',
            'jabatan' => 'HR Manager',
            'departemen' => 'HR',
            'status_karyawan' => 'tetap',
            'tanggal_masuk' => '2026-01-01',
            'email' => 'hr@example.com',
            'role' => 'hr_manager',
            'password' => bcrypt('password'),
        ]);

        $pegawai = User::query()->create([
            'nip' => 'PG-002',
            'nama' => 'Pegawai Dua',
            'jabatan' => 'Staff',
            'departemen' => 'Operasional',
            'status_karyawan' => 'tetap',
            'tanggal_masuk' => '2026-01-01',
            'email' => 'pegawai2@example.com',
            'role' => 'pegawai',
            'password' => bcrypt('password'),
        ]);

        $component = KpiComponent::query()->create([
            'jabatan' => 'Staff',
            'objectives' => 'Capai target bulanan',
            'strategy' => 'Monitoring harian',
            'bobot' => 1,
            'target' => 100,
            'tipe' => 'achievement',
            'is_active' => true,
        ]);

        KpiReport::query()->create([
            'user_id' => $pegawai->id,
            'kpi_component_id' => $component->id,
            'period_type' => 'monthly',
            'tanggal' => '2026-04-15',
            'period_label' => 'April 2026',
            'nilai_target' => 100,
            'nilai_aktual' => 95,
            'persentase' => 95,
            'score_label' => 'good',
            'status' => 'approved',
        ]);

        Sanctum::actingAs($hr);

        $response = $this->get('/api/export/reports/csv?tahun=2026&bulan=4');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="Laporan_KPI_4_2026.csv"');
        $content = $response->streamedContent();

        $this->assertStringContainsString('NIP,Nama,Divisi,"Komponen KPI",Target,Aktual,"Persentase (%)",Predikat,Tanggal,Status', $content);
        $this->assertStringContainsString('PG-002', $content);
        $this->assertStringContainsString('Good (80-100%)', $content);
    }
}
