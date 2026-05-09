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
            $table->date('tgl_presensi');
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_pulang')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'tidak_hadir', 'izin'])->default('hadir');
            $table->string('foto_masuk')->nullable();
            $table->string('foto_keluar')->nullable();
            $table->decimal('lat_masuk', 10, 7)->nullable();
            $table->decimal('long_masuk', 10, 7)->nullable();
            $table->unsignedInteger('durasi_menit')->default(0);
            $table->unsignedInteger('keterlambatan_menit')->default(0);
            $table->decimal('potongan', 10, 2)->default(0);
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
