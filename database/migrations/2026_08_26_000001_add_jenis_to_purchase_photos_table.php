<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_photos', function (Blueprint $table) {
            // 'nota' = foto nota; 'bukti_transfer' = bukti pembayaran/transfer.
            $table->string('jenis')->default('nota')->after('purchase_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_photos', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropColumn('jenis');
        });
    }
};
