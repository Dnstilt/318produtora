<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'sections';

        $hasVideoPublicId = Schema::hasColumn($tableName, 'video_public_id');
        $hasWebmDesktop = Schema::hasColumn($tableName, 'video_webm_desktop');
        $hasMp4Desktop = Schema::hasColumn($tableName, 'video_mp4_desktop');
        $hasWebmMobile = Schema::hasColumn($tableName, 'video_webm_mobile');
        $hasMp4Mobile = Schema::hasColumn($tableName, 'video_mp4_mobile');

        if ($hasVideoPublicId && $hasWebmDesktop && $hasMp4Desktop && $hasWebmMobile && $hasMp4Mobile) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use (
            $hasVideoPublicId,
            $hasWebmDesktop,
            $hasMp4Desktop,
            $hasWebmMobile,
            $hasMp4Mobile,
        ): void {
            if (!$hasVideoPublicId) {
                $table->string('video_public_id')->nullable()->after('id');
            }
            if (!$hasWebmDesktop) {
                $table->string('video_webm_desktop')->nullable();
            }
            if (!$hasMp4Desktop) {
                $table->string('video_mp4_desktop')->nullable();
            }
            if (!$hasWebmMobile) {
                $table->string('video_webm_mobile')->nullable();
            }
            if (!$hasMp4Mobile) {
                $table->string('video_mp4_mobile')->nullable();
            }
        });
    }

    public function down(): void
    {
        $tableName = 'sections';

        $columns = array_values(array_filter([
            Schema::hasColumn($tableName, 'video_public_id') ? 'video_public_id' : null,
            Schema::hasColumn($tableName, 'video_webm_desktop') ? 'video_webm_desktop' : null,
            Schema::hasColumn($tableName, 'video_mp4_desktop') ? 'video_mp4_desktop' : null,
            Schema::hasColumn($tableName, 'video_webm_mobile') ? 'video_webm_mobile' : null,
            Schema::hasColumn($tableName, 'video_mp4_mobile') ? 'video_mp4_mobile' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
