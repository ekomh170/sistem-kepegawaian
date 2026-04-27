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
        Schema::create('tb_laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('tb_karyawan')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('tb_admin')->cascadeOnDelete();
            $table->string('periode');
            $table->unsignedInteger('total_hadir')->default(0);
            $table->unsignedInteger('total_terlambat')->default(0);
            $table->unsignedInteger('total_tidak_hadir')->default(0);
            $table->decimal('estimasi_gaji', 12, 2)->default(0);
            $table->dateTime('tgl_generate');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_laporan');
    }
};
