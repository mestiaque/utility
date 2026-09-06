<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE bajar_list_items MODIFY description LONGTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bajar_list_items MODIFY description TEXT NULL');
    }
};
