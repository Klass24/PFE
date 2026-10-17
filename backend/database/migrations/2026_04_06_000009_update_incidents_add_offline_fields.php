<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->timestamp('reported_at')->nullable()->after('image');
            $table->timestamp('date_reception_serveur')->nullable()->after('reported_at');
            $table->string('device_id', 100)->nullable()->after('date_reception_serveur');
            $table->enum('sync_status', ['synced', 'pending'])->default('synced')->after('device_id');
        });

        // Étendre l'enum type avec les nouveaux cas
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE incidents MODIFY COLUMN type ENUM('accident','breakdown','flat_tire','traffic','police_check','mechanical_issue','other') NOT NULL"
        );
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['reported_at', 'date_reception_serveur', 'device_id', 'sync_status']);
        });

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE incidents MODIFY COLUMN type ENUM('accident','breakdown','flat_tire','traffic','other') NOT NULL"
        );
    }
};
