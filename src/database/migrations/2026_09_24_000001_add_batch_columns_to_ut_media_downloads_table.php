<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ut_media_downloads', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->after('user_id')->index(); // bulk download group
            $table->string('batch_title', 300)->nullable()->after('batch_id');  // playlist / channel name
        });
    }

    public function down(): void
    {
        Schema::table('ut_media_downloads', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn(['batch_id', 'batch_title']);
        });
    }
};
