<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('udr_uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->index();
            $table->foreignId('data_log_id')->constrained('udr_data_logs')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('extension', 20)->nullable()->index();
            $table->string('mime_type')->nullable()->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256_hash', 64)->nullable();
            $table->string('disk', 20)->default('public');
            $table->string('storage_path');
            $table->string('public_url')->nullable();
            $table->string('field_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('udr_uploaded_files');
    }
};
