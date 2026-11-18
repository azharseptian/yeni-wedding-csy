<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->string('kategori', 50)->comment('aksesoris, busana, dekorasi, perlengkapan_rias');
            $table->string('ukuran', 20)->nullable()->comment('S, M, L, XL, dst untuk busana');
            $table->unsignedInteger('jumlah_total')->default(0);
            $table->string('satuan', 30)->default('buah');
            $table->boolean('is_rawan_hilang')->default(false);
            $table->string('kondisi', 30)->default('baik');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
