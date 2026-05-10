<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_detail_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('tb_jadwal')->cascadeOnDelete();
            $table->foreignId('karyawan_id')->constrained('tb_karyawan')->cascadeOnDelete();
            $table->string('nama_lokasi');
            $table->text('alamat_lokasi')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('radius_meter')->default(50);
            $table->text('keterangan_pekerjaan')->nullable();
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->text('alasan_tolak')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_detail_pekerjaan');
    }
};
