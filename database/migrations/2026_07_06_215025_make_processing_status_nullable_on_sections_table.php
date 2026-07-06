<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('processing_status')->nullable()->default(null)->change();
        });

        // Corrigir os registros antigos que ficaram travados como "pending" sem ter vídeo processando
        DB::table('sections')
            ->where('processing_status', 'pending')
            ->whereNull('video_public_id')
            ->update(['processing_status' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('processing_status')->default('pending')->change();
        });
    }
};
