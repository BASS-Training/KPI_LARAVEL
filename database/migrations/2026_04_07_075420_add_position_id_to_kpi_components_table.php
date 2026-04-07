<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NORMALIZATION: kpi_components
 * Adds position_id FK to replace free-text 'jabatan'.
 * Old 'jabatan' string column is preserved for backward compatibility.
 *
 * Also adds position_id to sla table (same reason).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_components', function (Blueprint $table) {
            $table->foreignId('position_id')
                ->nullable()
                ->after('division_id')
                ->constrained('positions')
                ->nullOnDelete();

            $table->index(['position_id', 'is_active'], 'idx_kpi_comp_pos_active');
            $table->index(['division_id', 'is_active'], 'idx_kpi_comp_div_active');
        });

        // Same normalization for sla table
        Schema::table('sla', function (Blueprint $table) {
            $table->foreignId('position_id')
                ->nullable()
                ->after('jabatan')
                ->constrained('positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sla', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
        });

        Schema::table('kpi_components', function (Blueprint $table) {
            $table->dropIndex('idx_kpi_comp_pos_active');
            $table->dropIndex('idx_kpi_comp_div_active');
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
        });
    }
};
