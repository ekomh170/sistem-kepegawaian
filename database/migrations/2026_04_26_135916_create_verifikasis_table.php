<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tb_verifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presensi_id')->constrained('tb_presensi')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('tb_supervisor')->cascadeOnDelete();
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->text('catatan')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();
            $table->string('alasan_tolak')->nullable();
            $table->unique('presensi_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_verifikasi');
    }
};
