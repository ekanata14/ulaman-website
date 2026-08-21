<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekonsiliasi Q2: role UPL adalah super_admin|admin.
     * Kolom enum bawaan template diubah menjadi string agar lintas-DB aman
     * (MySQL app + SQLite test); nilai ditegakkan lewat validasi & konstanta.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->change();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at']);
            $table->enum('role', ['super_admin', 'user'])->default('user')->change();
        });
    }
};
