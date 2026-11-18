<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kota', 100);
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_basecamp')->default(false)->comment('Gudang/sanggar Yeni Wedding');
            $table->timestamps();

            $table->unique(['nama', 'kota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
