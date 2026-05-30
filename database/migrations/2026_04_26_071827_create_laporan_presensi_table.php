<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V3: gabungan tb_laporan + tb_rekap_presensi_bulanan.
     * - user_id NULL  => laporan agregat (semua karyawan)
     * - user_id != NULL => rekap per-karyawan (jumlah_* + total_potongan diisi)
     */
    public function up(): void
    {
        Schema::create('tb_laporan_presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('tb_user')->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('tb_user')->nullOnDelete();
            $table->string('judul');
            $table->string('periode'); // Harian: Y-m-d, Mingguan: Y-m-d, Bulanan: Y-m, Tahunan: Y
            $table->enum('jenis', ['Harian', 'Mingguan', 'Bulanan', 'Tahunan']);

            // Diisi hanya untuk rekap per-karyawan (user_id != NULL)
            $table->unsignedInteger('jumlah_hadir')->nullable();
            $table->unsignedInteger('jumlah_terlambat')->nullable();
            $table->unsignedInteger('jumlah_tidak_hadir')->nullable();
            $table->decimal('total_potongan', 12, 2)->nullable();

            $table->string('file_path')->nullable();
            $table->dateTime('tgl_generate')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index('jenis');
            $table->index('periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_laporan_presensi');
    }
};
