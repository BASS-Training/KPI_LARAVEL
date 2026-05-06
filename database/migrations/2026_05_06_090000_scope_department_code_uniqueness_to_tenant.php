<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_kode_unique');
            $table->unique(['tenant_id', 'kode'], 'departments_tenant_id_kode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_tenant_id_kode_unique');
            $table->unique('kode', 'departments_kode_unique');
        });
    }
};
