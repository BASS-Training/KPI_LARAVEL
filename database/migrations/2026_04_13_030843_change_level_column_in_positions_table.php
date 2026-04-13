<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->string('level', 100)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // Revert to original ENUM — existing rows with non-ENUM values will be truncated
            $table->enum('level', ['staff', 'supervisor', 'manager', 'director', 'executive'])
                ->default('staff')
                ->change();
        });
    }
};
