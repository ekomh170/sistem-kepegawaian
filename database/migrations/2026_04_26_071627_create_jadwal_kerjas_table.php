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
        Schema::create('tb_jadwal_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('tb_karyawan')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
            $table->time('jam_masuk');
            $table->time('jam_keluar');
            $table->string('lokasi_kerja');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_jadwal_kerja');
    }
};
