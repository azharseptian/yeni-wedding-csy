<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AB-06: pembayaran terpusat. Bukti transfer wajib untuk metode transfer (divalidasi di backend).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('jenis', 20)->comment('dp, cicilan, pelunasan');
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal_bayar');
            $table->string('metode', 20)->comment('tunai, transfer');
            $table->string('bank_pengirim', 50)->nullable();
            $table->string('nama_pengirim')->nullable();
            $table->string('bukti_transfer_path')->nullable();
            $table->foreignId('penerima_id')->nullable()->constrained('users')->nullOnDelete()->comment('Staf yang menerima/memverifikasi dana');
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_verifikasi', 20)->default('menunggu');
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_id', 'status_verifikasi']);
            $table->index('tanggal_bayar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
