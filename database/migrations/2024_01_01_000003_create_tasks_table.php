<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('judul');
            $table->string('jenis_pekerjaan');
            $table->enum('status', ['Selesai', 'Dalam Proses', 'Pending'])->default('Pending');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->boolean('ada_delay')->default(false);
            $table->boolean('ada_error')->default(false);
            $table->boolean('ada_komplain')->default(false);
            $table->text('deskripsi')->nullable();
            $table->foreignId('kpi_component_id')->nullable()->constrained('kpi_components')->nullOnDelete();
            $table->decimal('manual_score', 4, 2)->nullable();
            $table->foreignId('mapped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mapped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
