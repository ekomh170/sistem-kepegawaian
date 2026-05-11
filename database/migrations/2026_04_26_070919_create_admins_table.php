<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tb_user')->cascadeOnDelete();
            $table->string('nik');
            $table->string('no_hp');
            $table->unique('user_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_admin');
    }
};
