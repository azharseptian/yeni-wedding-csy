<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Komponen berfluktuasi (AB-05): melati segar, tenda per cm, dsb.
        Schema::create('dynamic_components', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->string('kategori', 50)->comment('bunga, tenda, dekorasi, lainnya');
            $table->string('tipe_harga', 20)->comment('flat, per_unit, per_cm');
            $table->decimal('harga_satuan', 15, 2);
            $table->string('satuan', 30)->comment('set, buah, cm, dll');
            $table->unsignedInteger('ukuran_min_cm')->nullable();
            $table->unsignedInteger('ukuran_max_cm')->nullable();
            $table->timestamp('harga_diperbarui_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_components');
    }
};
