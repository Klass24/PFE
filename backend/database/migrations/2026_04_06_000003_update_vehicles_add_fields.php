<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Supprimer l'unicité globale de plate_number → unicité par company_id
            $table->dropUnique(['plate_number']);
            $table->unique(['company_id', 'plate_number'], 'vehicles_company_plate_unique');

            $table->string('chassis_number', 50)->nullable()->after('plate_number');
            $table->enum('type', ['car', 'truck', 'van', 'motorcycle', 'bus', 'other'])
                  ->default('car')->after('chassis_number');
            $table->foreignId('current_driver_id')
                  ->nullable()
                  ->constrained('drivers')
                  ->nullOnDelete()
                  ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_company_plate_unique');
            $table->unique('plate_number');
            $table->dropForeign(['current_driver_id']);
            $table->dropColumn(['chassis_number', 'type', 'current_driver_id']);
        });
    }
};
