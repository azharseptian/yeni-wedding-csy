<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking', 30)->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('jenis_acara', 20)->default('akad_resepsi');
            $table->date('tanggal_acara');
            $table->time('jam_mulai_acara')->nullable();
            $table->time('jam_selesai_acara')->nullable();
            $table->text('alamat_acara')->nullable();
            $table->unsignedInteger('jumlah_tamu')->nullable();
            $table->string('status', 30)->default('penawaran');

            // Ringkasan finansial (di-cache, dihitung ulang oleh PaymentStatusService)
            $table->decimal('total_tagihan', 15, 2)->default(0);
            $table->decimal('total_dibayar', 15, 2)->default(0);
            $table->decimal('total_refund', 15, 2)->default(0);
            $table->decimal('total_biaya_vendor', 15, 2)->default(0);
            $table->decimal('total_margin', 15, 2)->default(0);

            // AB-04: ambang & batas pelunasan disalin dari system_parameters saat booking dibuat
            $table->unsignedTinyInteger('ambang_pelunasan_persen')->default(80);
            $table->date('batas_pelunasan')->nullable()->comment('tanggal_acara - 7 hari');

            // AB-02 & AB-05: penguncian tanggal dan harga
            $table->boolean('is_date_locked')->default(false);
            $table->timestamp('date_locked_at')->nullable();
            $table->boolean('is_price_locked')->default(false);
            $table->timestamp('price_locked_at')->nullable();
            $table->unsignedSmallInteger('revisi_ke')->default(0);

            // Jejak siklus status
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->timestamp('ambang_dicek_at')->nullable();
            $table->timestamp('siap_pelaksanaan_at')->nullable();
            $table->timestamp('lunas_at')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->text('alasan_batal')->nullable();

            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tanggal_acara', 'status']);
            $table->index(['status', 'batas_pelunasan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
