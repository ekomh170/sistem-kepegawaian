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
        Schema::create('tb_rekap_presensi_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('tb_karyawan')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('tb_admin')->nullOnDelete();
            $table->string('periode'); // Format: 2026-04
            $table->integer('jumlah_hadir')->default(0);
            $table->integer('jumlah_tidak_hadir')->default(0);
            $table->integer('jumlah_terlambat')->default(0);
            $table->decimal('total_potongan_keterlambatan', 12, 2)->default(0);
            $table->decimal('gaji_pokok', 12, 2)->default(0);
            $table->decimal('gaji_bersih', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'final', 'dibatalkan'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_rekap_presensi_bulanan');
    }
};
