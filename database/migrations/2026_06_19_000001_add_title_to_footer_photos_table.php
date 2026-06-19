<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'footer_photos';

        if (Schema::hasColumn($tableName, 'title')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->string('title')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        $tableName = 'footer_photos';

        if (!Schema::hasColumn($tableName, 'title')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn('title');
        });
    }
};

