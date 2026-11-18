<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paket Dasar berversi (AB-05): setiap perubahan harga membuat baris versi baru.
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30);
            $table->unsignedSmallInteger('versi')->default(1);
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->json('rincian')->nullable()->comment('Daftar item yang termasuk paket');
            $table->decimal('harga_dasar', 15, 2);
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['kode', 'versi']);
            $table->index(['kode', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
