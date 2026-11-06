<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ut_media_downloads', function (Blueprint $table) {
            $table->text('thumbnail')->nullable()->after('title'); // preview in the bulk progress list
        });
    }

    public function down(): void
    {
        Schema::table('ut_media_downloads', function (Blueprint $table) {
            $table->dropColumn('thumbnail');
        });
    }
};
