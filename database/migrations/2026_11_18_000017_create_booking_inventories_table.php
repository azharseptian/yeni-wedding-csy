<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist barang keluar & kembali per acara.
        Schema::create('booking_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->boolean('is_keluar')->default(false);
            $table->timestamp('keluar_at')->nullable();
            $table->foreignId('dikeluarkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_kembali')->default(false);
            $table->unsignedInteger('jumlah_kembali')->nullable();
            $table->timestamp('kembali_at')->nullable();
            $table->foreignId('dikembalikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kondisi_kembali', 30)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_inventories');
    }
};
