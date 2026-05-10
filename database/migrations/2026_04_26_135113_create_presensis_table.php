<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('tb_karyawan')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('tb_jadwal')->nullOnDelete();
            $table->date('tanggal');
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_keluar')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->string('foto_keluar')->nullable();
            $table->decimal('latitude_masuk', 10, 7)->nullable();
            $table->decimal('longitude_masuk', 10, 7)->nullable();
            $table->decimal('latitude_keluar', 10, 7)->nullable();
            $table->decimal('longitude_keluar', 10, 7)->nullable();
            $table->unsignedInteger('menit_terlambat')->default(0);
            $table->decimal('potongan_terlambat', 10, 2)->default(0);
            $table->enum('status_presensi', ['hadir', 'terlambat', 'tidak_hadir', 'izin'])->default('hadir');
            $table->enum('status_valid', ['pending', 'valid', 'tidak_valid'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_presensi');
    }
};
