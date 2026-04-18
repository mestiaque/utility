<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bajar_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('bajar_list_groups')->onDelete('cascade');
            $table->string('item_name');
            $table->string('brand')->nullable();
            $table->string('source')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'purchased', 'hold'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bajar_list_items');
    }
};
