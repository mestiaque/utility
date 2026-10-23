<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ut_media_downloads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('url');
            $table->string('platform', 100)->nullable();
            $table->string('title', 500)->nullable();
            $table->string('format', 50);                        // option key, e.g. video-1080 / audio-mp3
            $table->string('output_type', 10)->default('video'); // video | audio
            $table->string('extension', 10)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('stage', 100)->nullable();            // human text: "Merging audio & video"
            $table->decimal('progress', 5, 2)->nullable();       // null = unknown (indeterminate)
            $table->unsignedBigInteger('speed')->nullable();     // bytes / second
            $table->unsignedInteger('eta')->nullable();          // seconds
            $table->unsignedBigInteger('filesize')->nullable();  // bytes (estimate until completed)
            $table->string('file_path', 1000)->nullable();       // temp file awaiting pickup
            $table->string('file_name', 255)->nullable();        // safe name sent to the browser
            $table->text('error_message')->nullable();           // user-friendly message only
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ut_media_downloads');
    }
};
