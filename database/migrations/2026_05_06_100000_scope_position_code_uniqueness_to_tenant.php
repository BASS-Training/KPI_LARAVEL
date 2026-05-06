<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique('positions_kode_unique');
            $table->unique(['tenant_id', 'kode'], 'positions_tenant_id_kode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique('positions_tenant_id_kode_unique');
            $table->unique('kode', 'positions_kode_unique');
        });
    }
};
