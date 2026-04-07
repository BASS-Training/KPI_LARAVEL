<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI SUMMARIES — Pre-aggregated per-user per-period totals
 *
 * Purpose: avoid recomputing SUM(final_score) on every dashboard load.
 * Recomputed by a background job (KpiSummaryJob) after any kpi_result changes.
 *
 * One row = one employee's complete KPI score for one period.
 *
 * Used by:
 *   - Pegawai dashboard  → "nilai KPI bulan ini"
 *   - HR dashboard       → monitoring table
 *   - Direktur dashboard → overview stats
 *   - Analytics charts   → trend line per month
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_summaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('period_label', 20);
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])
                ->default('monthly');

            // Legacy 0-5 score system total
            $table->decimal('total_score', 8, 4)->default(0)
                ->comment('Sum of final_score from kpi_results (0-5 scale)');

            // New percentage-based achievement
            $table->decimal('achievement_rate', 8, 2)->default(0)
                ->comment('Weighted average of achievement_rate from kpi_results');

            $table->enum('score_label', ['excellent', 'good', 'average', 'bad'])->nullable();

            // How many KPI components were evaluated
            $table->unsignedSmallInteger('kpi_count')->default(0);

            // How many components reached >= 80%
            $table->unsignedSmallInteger('achieved_count')->default(0);

            // Timestamp of last recalculation
            $table->timestamp('recalculated_at')->nullable();

            $table->timestamps();

            // One summary per user per period
            $table->unique(['user_id', 'period_label', 'period_type'], 'uniq_summary_user_period');

            // HR monitoring: sort all employees by achievement_rate desc in a period
            $table->index(['period_label', 'achievement_rate'], 'idx_summary_period_rate');

            // Trend charts: all summaries for a user ordered by period
            $table->index(['user_id', 'period_type', 'period_label'], 'idx_summary_user_trend');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_summaries');
    }
};
