<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->index()->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('deskripsi', 255);
            $table->decimal('qty', 12, 2);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('harga_satuan', 18, 2)->nullable();
            $table->boolean('harga_estimasi')->default(false);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('diskon_tipe')->default('NONE');
            $table->decimal('diskon_nilai', 18, 2)->default(0);
            $table->decimal('diskon_amount', 18, 2)->default(0);
            $table->decimal('alokasi_diskon_bundle', 18, 2)->default(0);
            $table->decimal('alokasi_diskon_nota', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('net_total', 18, 2)->default(0);
            $table->string('remark', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
