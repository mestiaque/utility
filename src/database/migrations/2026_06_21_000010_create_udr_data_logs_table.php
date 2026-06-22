<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('udr_data_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->index();
            $table->string('method', 10)->index();
            $table->text('full_url');
            $table->string('path')->index();
            $table->string('host')->nullable();
            $table->string('scheme', 10)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('content_type')->nullable()->index();
            $table->unsignedBigInteger('content_length')->nullable();
            $table->unsignedBigInteger('request_size')->default(0);
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('parsed_body')->nullable();
            $table->json('form_fields')->nullable();
            $table->json('file_info')->nullable();
            $table->longText('raw_body')->nullable();
            $table->boolean('has_files')->default(false)->index();
            $table->boolean('is_json')->default(false);
            $table->boolean('is_xml')->default(false);
            $table->boolean('is_binary')->default(false);
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['ip_address', 'received_at']);
            $table->index(['method', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('udr_data_logs');
    }
};
