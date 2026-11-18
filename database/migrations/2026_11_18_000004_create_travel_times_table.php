<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parameter t(l(j), l(i)) untuk algoritma deteksi bentrok estafet.
        Schema::create('travel_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('locations')->cascadeOnDelete();
            $table->unsignedSmallInteger('waktu_tempuh_menit');
            $table->unsignedSmallInteger('waktu_persiapan_menit')->default(30);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['from_location_id', 'to_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_times');
    }
};
