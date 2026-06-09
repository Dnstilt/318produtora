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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->longText('description_text')->nullable();

            $table->string('video_public_id',)->nullable()->after('id');
            
            $table->enum('processing_status', ['pending', 'processing', 'done', 'error'])->default('pending');
            $table->text('processing_error')->nullable();

            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
