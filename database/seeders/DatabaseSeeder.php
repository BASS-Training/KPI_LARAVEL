<?php

namespace Database\Seeders;

use App\Models\KpiComponent;
use App\Models\Setting;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nip' => 'BASS-DIRUT-01-2016',
                'nama' => 'Eva Rosmalia',
                'jabatan' => 'Direktur Utama',
                'departemen' => 'Board of Director',
                'status_karyawan' => 'Tetap',
                'tanggal_masuk' => '2016-09-26',
                'no_hp' => '081298685372',
                'email' => 'eva.rosmalia@bass.co.id',
                'role' => 'direktur',
            ],
            [
                'nip' => 'BASS-DIR-01-2020',
                'nama' => 'Daffa Muhammad Ardian',
                'jabatan' => 'Direktur',
                'departemen' => 'Board of Director',
                'status_karyawan' => 'Tetap',
                'tanggal_masuk' => '2020-01-11',
                'no_hp' => '082125277435',
                'email' => 'daffa.ardian@bass.co.id',
                'role' => 'direktur',
            ],
            [
                'nip' => 'BASS-HR-01-2026',
                'nama' => 'Katry Oktariani',
                'jabatan' => 'HR & GA Manager',
                'departemen' => 'HR & GA',
                'status_karyawan' => 'Kontrak',
                'tanggal_masuk' => '2026-01-02',
                'no_hp' => '08176409005',
                'email' => 'katry.oktariani@bass.co.id',
                'role' => 'hr_manager',
            ],
            [
                'nip' => 'BASS-MKT-01-2024',
                'nama' => 'Nadia Permatasari',
                'jabatan' => 'Marketing & Sales',
                'departemen' => 'Marketing',
                'status_karyawan' => 'Tetap',
                'tanggal_masuk' => '2024-02-01',
                'no_hp' => '081345678901',
                'email' => 'nadia.permatasari@bass.co.id',
                'role' => 'pegawai',
            ],
            [
                'nip' => 'BASS-FIN-01-2024',
                'nama' => 'Rizki Pratama',
                'jabatan' => 'Finance',
                'departemen' => 'Finance',
                'status_karyawan' => 'Tetap',
                'tanggal_masuk' => '2024-03-15',
                'no_hp' => '081377788899',
                'email' => 'rizki.pratama@bass.co.id',
                'role' => 'pegawai',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['nip' => $user['nip']],
                $user + ['password' => Hash::make(strtolower($user['nip'].'|'.$user['nama']))]
            );
        }

        $components = [
            [
                'jabatan' => 'Marketing & Sales',
                'objectives' => 'Sales Revenue',
                'strategy' => 'Penjualan new products dan akuisisi pelanggan baru',
                'bobot' => 0.70,
                'target' => 3500000000,
                'tipe' => 'achievement',
                'catatan' => 'Dinilai bulanan.',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Marketing & Sales',
                'objectives' => 'Customer Satisfaction Index',
                'strategy' => 'Menjaga kualitas layanan dan tindak lanjut pelanggan',
                'bobot' => 0.20,
                'target' => 3.50,
                'tipe' => 'csi',
                'catatan' => 'Menggunakan input manual_score.',
                'is_active' => true,
            ],
            [
                'jabatan' => 'Marketing & Sales',
                'objectives' => 'Lead Time Proposal',
                'strategy' => 'Memastikan proposal terbit sesuai SLA',
                'bobot' => 0.10,
                'target' => 0,
                'tipe' => 'zero_delay',
                'catatan' => null,
                'is_active' => true,
            ],
            [
                'jabatan' => 'Finance',
                'objectives' => 'Ketepatan Pembuatan Invoice',
                'strategy' => 'Menerbitkan invoice maksimal 1 hari kerja setelah closing',
                'bobot' => 0.60,
                'target' => 100,
                'tipe' => 'achievement',
                'catatan' => null,
                'is_active' => true,
            ],
            [
                'jabatan' => 'Finance',
                'objectives' => 'Zero Error Dokumen',
                'strategy' => 'Memastikan tidak ada salah input nominal atau data pajak',
                'bobot' => 0.40,
                'target' => 0,
                'tipe' => 'zero_error',
                'catatan' => null,
                'is_active' => true,
            ],
        ];

        foreach ($components as $component) {
            KpiComponent::updateOrCreate(
                [
                    'jabatan' => $component['jabatan'],
                    'objectives' => $component['objectives'],
                ],
                $component
            );
        }

        $slaList = [
            [
                'nama_pekerjaan' => 'Pembuatan Invoice',
                'jabatan' => 'Finance',
                'durasi_jam' => 8,
                'keterangan' => 'Selesai dalam 1 hari kerja setelah closing.',
            ],
            [
                'nama_pekerjaan' => 'Laporan P&L',
                'jabatan' => 'Accounting',
                'durasi_jam' => 24,
                'keterangan' => 'Terkirim tanggal 3 setiap bulan.',
            ],
            [
                'nama_pekerjaan' => 'Penawaran / Proposal',
                'jabatan' => 'Marketing & Sales',
                'durasi_jam' => 8,
                'keterangan' => 'Maksimal 1 hari kerja setelah permintaan masuk.',
            ],
        ];

        foreach ($slaList as $sla) {
            Sla::updateOrCreate(
                [
                    'nama_pekerjaan' => $sla['nama_pekerjaan'],
                    'jabatan' => $sla['jabatan'],
                ],
                $sla
            );
        }

        foreach ([
            'company' => 'PT. BASS Training Center & Consultant',
            'year' => '2026',
            'hr_manager_name' => 'Katry Oktariani',
            'hr_manager_nip' => 'BASS-HR-01-2026',
        ] as $key => $value) {
            Setting::setValue($key, $value);
        }
    }
}
