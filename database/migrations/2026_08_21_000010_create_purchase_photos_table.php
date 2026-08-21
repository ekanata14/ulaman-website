<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->index()->constrained('purchases')->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('nama_file_asli');
            $table->string('mime_type');
            $table->unsignedBigInteger('ukuran'); // byte
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_photos');
    }
};
