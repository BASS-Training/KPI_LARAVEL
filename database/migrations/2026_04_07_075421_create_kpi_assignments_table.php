<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI ASSIGNMENTS
 *
 * Maps KPIs to individual users with per-user target and weight.
 * This is the core of the "assign KPI to employee" feature.
 *
 * Rules:
 *   - One user can have many KPI assignments (different KPIs)
 *   - One KPI can be assigned to many users
 *   - Each assignment has its own target, weight, and validity period
 *   - UNIQUE(kpi_id, user_id, start_date) prevents duplicate assignments for the same period
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_id')
                ->constrained('kpis')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Per-assignment override of KPI defaults
            $table->decimal('target', 20, 4)->nullable()
                ->comment('Override kpis.default_target for this specific user');

            $table->decimal('weight', 5, 2)->default(0.00)
                ->comment('Override kpis.default_weight; 0.00–100.00');

            // Validity window — allows archiving old assignments when KPIs change
            $table->date('start_date');
            $table->date('end_date')->nullable()
                ->comment('NULL = no expiry (open-ended assignment)');

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('HR manager who created this assignment');

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent exact duplicate: same KPI to same user starting same date
            $table->unique(['kpi_id', 'user_id', 'start_date'], 'uniq_kpi_user_start');

            // Most queried: "all active KPIs for user X"
            $table->index(['user_id', 'is_active', 'start_date'], 'idx_assign_user_active');

            // "all users assigned to KPI Y"
            $table->index(['kpi_id', 'is_active'], 'idx_assign_kpi_active');

            // Period validity filter
            $table->index(['start_date', 'end_date'], 'idx_assign_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_assignments');
    }
};
