<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_tag', function (Blueprint $table): void {
            $table->unsignedInteger('position')->default(0)->after('tag_id');
        });
    }

    public function down(): void
    {
        Schema::table('article_tag', function (Blueprint $table): void {
            $table->dropColumn('position');
        });
    }
};
