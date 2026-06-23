<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_base_url')->default('');
            $table->string('api_token')->nullable();
            $table->string('fetch_employee_url')->default('');
            $table->string('sync_attendance_url')->default('');
            $table->string('employee_sync_cron')->default('0 * * * *');
            $table->string('attendance_fetch_cron')->default('*/15 * * * *');
            $table->unsignedSmallInteger('adms_port')->default(5015);
            $table->string('adms_protocol')->default('HTTP Push');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adms_settings');
    }
};
