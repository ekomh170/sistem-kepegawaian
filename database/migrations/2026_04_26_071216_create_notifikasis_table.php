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
        Schema::create('tb_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tb_user')->cascadeOnDelete();
            $table->enum('tipe', ['info', 'peringatan', 'urgent'])->default('info');
            $table->text('pesan');
            $table->boolean('terbaca')->default(false);
            $table->dateTime('tgl_kirim');
            $table->string('channel');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_notifikasi');
    }
};
