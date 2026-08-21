<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('nama');
            $table->string('tipe_diskon');
            $table->decimal('nilai_diskon', 18, 2)->default(0);
            $table->decimal('basis_amount', 18, 2)->default(0);
            $table->decimal('diskon_amount', 18, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bundles');
    }
};
