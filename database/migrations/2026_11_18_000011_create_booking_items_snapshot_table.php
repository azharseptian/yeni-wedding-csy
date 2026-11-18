<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot harga terkunci (price-lock AB-05). Tidak boleh merujuk harga master secara langsung.
        Schema::create('booking_items_snapshot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('item_type', 30)->comment('paket, komponen_dinamis, layanan_vendor, tambahan');
            $table->nullableMorphs('source');
            $table->string('nama_item');
            $table->string('tipe_harga', 20)->default('flat');
            $table->decimal('kuantitas', 12, 2)->default(1);
            $table->string('satuan', 30)->nullable();
            $table->unsignedInteger('ukuran_cm')->nullable()->comment('Untuk komponen per cm, mis. tenda');
            $table->decimal('harga_satuan_klien', 15, 2);
            $table->decimal('tarif_vendor_satuan', 15, 2)->nullable()->comment('Rahasia - hanya Owner');
            $table->decimal('subtotal_klien', 15, 2);
            $table->decimal('subtotal_vendor', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->unsignedSmallInteger('versi_paket')->nullable();
            $table->json('snapshot_data')->nullable()->comment('Salinan lengkap data master saat dikunci');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->unsignedSmallInteger('revisi_ke')->default(0);
            $table->timestamps();

            $table->index(['booking_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items_snapshot');
    }
};
