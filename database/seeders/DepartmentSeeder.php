<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /** Canonical 5 departments (tanpa divisi sub-level) */
    private const DEPARTMENTS = [
        ['nama' => 'Board of Director',      'kode' => 'BOD',  'deskripsi' => 'Dewan direksi dan pengambil keputusan strategis perusahaan.'],
        ['nama' => 'Finance & Accounting',   'kode' => 'FNA',  'deskripsi' => 'Pengelolaan keuangan, akuntansi, dan pelaporan fiskal.'],
        ['nama' => 'HR & GA',                'kode' => 'HGA',  'deskripsi' => 'Human Resources dan General Affairs.'],
        ['nama' => 'Business Development',   'kode' => 'BDV',  'deskripsi' => 'Pengembangan bisnis, marketing, dan penjualan.'],
        ['nama' => 'Research & Development', 'kode' => 'RND',  'deskripsi' => 'Riset, inovasi, dan pengembangan produk.'],
    ];

    public function run(): void
    {
        // Ensure at least one division exists as the parent
        $division = Division::query()->firstOrCreate(
            ['kode' => 'BASS'],
            [
                'nama'      => 'BASS Training Center',
                'deskripsi' => 'Divisi utama PT. BASS Training Center & Consultant',
                'is_active' => true,
            ]
        );

        foreach (self::DEPARTMENTS as $dept) {
            Department::query()->updateOrCreate(
                ['kode' => $dept['kode']],
                [
                    'nama'        => $dept['nama'],
                    'division_id' => $division->id,
                    'deskripsi'   => $dept['deskripsi'],
                    'is_active'   => true,
                ]
            );
        }
    }
}
