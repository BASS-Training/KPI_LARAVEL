<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            // Make role_id nullable so department-based indicators work without a role
            $table->foreignId('role_id')->nullable()->change();

            // Formula JSON: {"type":"percentage"} | {"type":"threshold","thresholds":[...]}
            // | {"type":"conditional"} | {"type":"zero_penalty"} | {"type":"flat","score":1.0}
            $table->json('formula')->nullable()->after('default_target_value');

            // Link indicator to a specific department (null = applies to all departments for the role)
            $table->foreignId('department_id')
                ->nullable()
                ->after('formula')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index('department_id');
        });

        Schema::table('kpi_scores', function (Blueprint $table) {
            // Status column required by KpiService but missing from original migration
            if (! Schema::hasColumn('kpi_scores', 'status')) {
                $table->string('status', 20)->default('average')->after('grade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kpi_indicators', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('formula');
            $table->foreignId('role_id')->nullable(false)->change();
        });

        Schema::table('kpi_scores', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
