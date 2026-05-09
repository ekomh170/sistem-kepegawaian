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
        Schema::create('tb_karyawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tb_user')->cascadeOnDelete();
            $table->string('nik')->unique();
            $table->string('posisi_karyawan');
            $table->string('no_ktp')->nullable();
            $table->string('no_hp');
            $table->text('alamat')->nullable();
            $table->string('foto')->nullable();
            $table->date('tgl_masuk');
            $table->enum('status_kontrak', ['kontrak', 'tetap', 'freelance']);
            $table->string('bidang_tugas')->nullable();
            $table->decimal('gaji_pokok', 12, 2)->default(0);
            $table->unique('user_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_karyawan');
    }
};
