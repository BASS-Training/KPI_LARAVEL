<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NORMALIZATION: departments
 * Replaces free-text 'departemen' column on users.
 * Each department belongs to a division (1 division → N departments).
 *
 * Existing data mapping:
 *   Board of Director  → division: null (top-level)
 *   HR & GA            → division: HRGA
 *   Marketing          → division: MKT
 *   Finance            → division: FIN
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->string('nama', 100);
            $table->string('kode', 20)->unique()->comment('Short code, e.g. HRGA, MKT, FIN, BOD');

            // Parent division — nullable because top-level depts may not belong to a division
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete();

            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite index for common queries: "all active depts in division X"
            $table->index(['division_id', 'is_active'], 'idx_dept_division_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
