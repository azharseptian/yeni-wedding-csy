<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AB-03: refund DP penuh (100%).
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal_refund');
            $table->string('metode', 20)->comment('tunai, transfer');
            $table->string('bank_tujuan', 50)->nullable();
            $table->string('nomor_rekening_tujuan', 30)->nullable();
            $table->string('atas_nama')->nullable();
            $table->string('bukti_refund_path')->nullable();
            $table->text('alasan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal_refund');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
