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
            // id, user_id, nik, jenis_kelamin, tgl_masuk, status_kontrak, no_hp, lokasi_tugas
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nik');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->date('tgl_masuk');
            $table->enum('status_kontrak', ['Kontrak', 'Tetap']);
            $table->string('no_hp');
            $table->string('lokasi_tugas');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->softDeletes();
            $table->index(['deleted_at']);
            $table->unique(['user_id']);
            $table->unique(['nik']);
            

            $table->timestamps();
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
