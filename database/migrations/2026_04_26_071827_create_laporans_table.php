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
            $table->string('judul')->nullable();
            $table->string('jenis')->nullable();
            $table->string('periode')->nullable();
            $table->text('filter')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('generated_by')->constrained('tb_user')->cascadeOnDelete();
            $table->dateTime('tgl_generate')->useCurrent();
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
