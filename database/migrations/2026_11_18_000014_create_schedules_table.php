<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penugasan sumber daya per acara (input algoritma deteksi bentrok estafet & kapasitas kru).
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Tim internal / vendor keluarga');
            $table->foreignId('vendor_service_id')->nullable()->constrained('vendor_services')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('peran', 30)->comment('mua, asisten_rias, dekorator, kru_dekorasi, mc, among_tamu, vendor');
            $table->boolean('is_tenaga_lepas')->default(false);
            $table->string('nama_tenaga_lepas')->nullable();
            $table->string('phone_tenaga_lepas', 20)->nullable();
            $table->date('tanggal')->comment('Tanggal kerja (boleh H-1/H-2 untuk dekorasi)');
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->string('status', 20)->default('dijadwalkan');
            $table->boolean('is_override')->default(false);
            $table->text('alasan_override')->nullable();
            $table->foreignId('override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('override_at')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
            $table->index(['tanggal', 'peran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
