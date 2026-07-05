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
        Schema::table('sections', function (Blueprint $table) {
            $table->string('mobile_video_public_id')->nullable()->after('video_public_id');
            $table->string('mobile_processing_status')->nullable()->after('processing_error');
            $table->text('mobile_processing_error')->nullable()->after('mobile_processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_video_public_id',
                'mobile_processing_status',
                'mobile_processing_error',
            ]);
        });
    }
};
