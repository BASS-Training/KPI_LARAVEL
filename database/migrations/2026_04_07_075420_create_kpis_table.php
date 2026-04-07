<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MASTER KPI TABLE
 *
 * Decouples KPI definitions from positions/users.
 * One KPI can be assigned to multiple users (via kpi_assignments),
 * each with their own target and weight.
 *
 * Relationship:
 *   kpis → kpi_assignments → users
 *   kpis → kpi_assignments → kpi_results
 *
 * Coexists with kpi_components (legacy; position-based templates).
 * New KPIs should be created here; kpi_components kept for backward compat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();

            // Optionally linked to old kpi_component for migration continuity
            $table->foreignId('kpi_component_id')
                ->nullable()
                ->constrained('kpi_components')
                ->nullOnDelete()
                ->comment('Link to legacy kpi_components for migration bridge');

            $table->string('title', 255);
            $table->text('description')->nullable();

            // Measurement type
            $table->string('type', 30)->default('achievement')
                ->comment('achievement | percentage | boolean | csi | zero_delay | zero_error | zero_complaint | number');

            // Measurement period
            $table->enum('period', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])
                ->default('monthly');

            // Unit label shown in UI (Rp, %, leads, laporan, unit)
            $table->string('unit', 50)->nullable();

            // Which division this KPI is primarily for (can still be assigned cross-division)
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete();

            // Default target — can be overridden per assignment
            $table->decimal('default_target', 20, 4)->nullable();

            // Default weight — can be overridden per assignment
            $table->decimal('default_weight', 5, 2)->default(0.00)
                ->comment('0.00–100.00 representing percentage weight');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['division_id', 'is_active'], 'idx_kpis_div_active');
            $table->index(['type', 'period'], 'idx_kpis_type_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
