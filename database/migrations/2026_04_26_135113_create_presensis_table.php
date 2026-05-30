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
            $table->foreignId('user_id')->constrained('tb_user')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('tb_jadwal_pekerjaan')->nullOnDelete();
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
            $table->enum('status_presensi', ['hadir', 'terlambat', 'tidak_hadir', 'izin'])->default('tidak_hadir');

            // V3: verifikasi inline (dulu tabel tb_verifikasi terpisah). status_valid V2 dilebur ke sini.
            $table->enum('status_verifikasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('tb_user')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->dateTime('tgl_verifikasi')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'tanggal']);
            $table->index('status_presensi');
            $table->index('status_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_presensi');
    }
};
