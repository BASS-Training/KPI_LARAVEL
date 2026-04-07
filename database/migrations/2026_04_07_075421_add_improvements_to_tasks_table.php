<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TASK TABLE IMPROVEMENTS
 *
 * New columns:
 *   duration_minutes  — auto-computed from waktu_mulai..waktu_selesai (stored for perf)
 *   sla_id            — FK to sla table (which SLA rule this task was evaluated against)
 *   sla_status        — 'on_time' | 'late' | 'not_applicable'
 *   score_generated   — flag: has this task already been included in a KPI result?
 *
 * New indexes:
 *   (user_id, tanggal)         — most common query pattern for pegawai dashboard
 *   (kpi_component_id, tanggal)— aggregation by component per period
 *   (tanggal, status)          — HR filter: tasks by date range + status
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Computed duration stored for fast aggregation
            $table->unsignedSmallInteger('duration_minutes')
                ->nullable()
                ->after('waktu_selesai')
                ->comment('waktu_selesai - waktu_mulai in minutes, NULL if times not set');

            // Which SLA rule was this task measured against
            $table->foreignId('sla_id')
                ->nullable()
                ->after('kpi_component_id')
                ->constrained('sla')
                ->nullOnDelete();

            // SLA evaluation result
            $table->enum('sla_status', ['on_time', 'late', 'not_applicable'])
                ->default('not_applicable')
                ->after('sla_id');

            // Prevents double-counting in KPI recalculation jobs
            $table->boolean('score_generated')
                ->default(false)
                ->after('sla_status');

            // Performance indexes
            $table->index(['user_id', 'tanggal'], 'idx_tasks_user_date');
            $table->index(['kpi_component_id', 'tanggal'], 'idx_tasks_kpi_date');
            $table->index(['tanggal', 'status'], 'idx_tasks_date_status');
            $table->index(['sla_status'], 'idx_tasks_sla_status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_user_date');
            $table->dropIndex('idx_tasks_kpi_date');
            $table->dropIndex('idx_tasks_date_status');
            $table->dropIndex('idx_tasks_sla_status');
            $table->dropForeign(['sla_id']);
            $table->dropColumn(['duration_minutes', 'sla_id', 'sla_status', 'score_generated']);
        });
    }
};
