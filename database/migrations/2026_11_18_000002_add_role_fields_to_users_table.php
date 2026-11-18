<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->after('id')->constrained('roles')->restrictOnDelete();
            $table->string('jabatan', 30)->nullable()->after('name')->comment('Khusus tim internal: mua, asisten_rias, dekorator, kru_dekorasi');
            $table->string('phone', 20)->nullable()->after('email')->comment('Nomor WhatsApp format 62xxx');
            $table->boolean('is_active')->default(true)->after('password');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['jabatan', 'phone', 'is_active', 'deleted_at']);
        });
    }
};
