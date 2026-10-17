<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('status', ['active', 'suspended'])->default('active')->after('is_active');
            $table->unsignedSmallInteger('max_vehicles')->default(10)->after('status');
            $table->unsignedSmallInteger('max_drivers')->default(10)->after('max_vehicles');
            $table->unsignedSmallInteger('max_users')->default(5)->after('max_drivers');
        });

        // Synchroniser status avec is_active existant
        \Illuminate\Support\Facades\DB::statement(
            "UPDATE companies SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'suspended' END"
        );
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['status', 'max_vehicles', 'max_drivers', 'max_users']);
        });
    }
};
