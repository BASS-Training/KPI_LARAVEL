<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kpi_components', function (Blueprint $table) {
            $table->id();
            $table->string('jabatan');
            $table->string('objectives');
            $table->text('strategy');
            $table->decimal('bobot', 4, 2);
            $table->decimal('target', 20, 2)->nullable();
            $table->enum('tipe', ['zero_delay', 'zero_error', 'zero_complaint', 'achievement', 'csi']);
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_components');
    }
};
