<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_jadwal_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tb_user')->cascadeOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('tb_user')->nullOnDelete();
            $table->date('tanggal_kerja');
            $table->time('jam_masuk')->default('08:00:00');
            $table->time('jam_pulang')->default('16:00:00');
            $table->boolean('hari_libur')->default(false);
            $table->enum('status', ['aktif', 'dibatalkan'])->default('aktif');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'tanggal_kerja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_jadwal_pekerjaan');
    }
};
