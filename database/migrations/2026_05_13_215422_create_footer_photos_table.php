<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('footer_photos', function (Blueprint $table) {
            $table->id();
            $table->string('photo_avif')->nullable();
            $table->string('photo_webp')->nullable();
            $table->string('photo_jpg')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footer_photos');
    }
};
