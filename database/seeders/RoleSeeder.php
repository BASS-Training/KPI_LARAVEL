<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    private const ROLES = [
        // Board of Director
        ['name' => 'Direktur Utama',     'description' => 'Pemimpin tertinggi perusahaan.'],
        ['name' => 'Direktur',           'description' => 'Anggota dewan direksi.'],
        // Business Development
        ['name' => 'Marketing & Sales',  'description' => 'Tenaga pemasaran dan penjualan.'],
        ['name' => 'Digital Marketing',  'description' => 'Pengelola pemasaran digital.'],
        // Finance & Accounting
        ['name' => 'Finance',            'description' => 'Pengelola keuangan operasional.'],
        ['name' => 'Accounting',         'description' => 'Pencatatan dan pelaporan akuntansi.'],
        // HR & GA
        ['name' => 'HR & GA Manager',    'description' => 'Manajer SDM dan administrasi umum.'],
        ['name' => 'Admin GA',           'description' => 'Staf administrasi umum.'],
        ['name' => 'Driver',             'description' => 'Pengemudi operasional.'],
        ['name' => 'Office Boy',         'description' => 'Pendukung operasional kantor.'],
        // Research & Development
        ['name' => 'R&D Staff',          'description' => 'Staf riset dan pengembangan.'],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::query()->updateOrCreate(
                ['slug' => Str::slug($role['name'])],
                [
                    'name'        => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
