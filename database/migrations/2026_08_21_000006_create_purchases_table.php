<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->date('tanggal')->index();
            $table->foreignId('supplier_id')->nullable()->index()->constrained('suppliers')->nullOnDelete();
            $table->string('nomor_nota', 50)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('metode_bayar')->nullable();
            $table->text('remark')->nullable();
            $table->string('status')->default('draft');

            // Total ternormalisasi (semua DECIMAL(18,2), tanpa float)
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('total_diskon_item', 18, 2)->default(0);
            $table->decimal('total_diskon_bundle', 18, 2)->default(0);
            $table->string('diskon_nota_tipe')->nullable();
            $table->decimal('diskon_nota_nilai', 18, 2)->default(0);
            $table->decimal('diskon_nota_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0)->index();

            $table->boolean('needs_review')->default(false);
            $table->unsignedBigInteger('project_id')->nullable()->index(); // Q8: disiapkan, UI multi-proyek menyusul
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tanggal', 'supplier_id']);
            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
