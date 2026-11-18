<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dual-pricing vendor keluarga (AB-08). margin dihitung otomatis oleh database.
        Schema::create('vendor_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_layanan');
            $table->string('kategori', 50)->comment('catering, mc, among_tamu, dokumentasi, dll');
            $table->string('satuan', 30)->default('paket');
            $table->decimal('tarif_vendor', 15, 2)->comment('Modal ke keluarga - RAHASIA dari klien & tim internal');
            $table->decimal('tarif_klien', 15, 2)->comment('Harga jual ke pengantin');
            $table->decimal('margin', 15, 2)->storedAs('tarif_klien - tarif_vendor');
            $table->boolean('is_active')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_services');
    }
};
