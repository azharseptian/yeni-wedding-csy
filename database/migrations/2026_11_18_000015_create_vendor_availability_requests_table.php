<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengajuan pembaruan ketersediaan jadwal oleh Vendor Keluarga (butuh persetujuan Admin).
        Schema::create('vendor_availability_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('ketersediaan', 20)->comment('tersedia, tidak_tersedia');
            $table->text('alasan')->nullable();
            $table->string('status', 20)->default('menunggu');
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditinjau_at')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();

            $table->index(['vendor_user_id', 'tanggal']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_availability_requests');
    }
};
