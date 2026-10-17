<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE missions MODIFY start_date DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE missions MODIFY start_date DATETIME NOT NULL');
    }
};
