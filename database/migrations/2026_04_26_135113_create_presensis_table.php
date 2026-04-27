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
        Schema::create('tb_presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('tb_karyawan')->cascadeOnDelete();
            $table->foreignId('jadwal_kerja_id')->constrained('tb_jadwal_kerja')->cascadeOnDelete();
            $table->foreignId('lokasi_gps_id')->constrained('tb_lokasi_gps')->cascadeOnDelete();
            $table->date('tgl_presensi');
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_keluar')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'tidak_hadir', 'izin'])->default('hadir');
            $table->string('foto_masuk')->nullable();
            $table->string('foto_keluar')->nullable();
            $table->unsignedInteger('durasi_menit')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_presensi');
    }
};
