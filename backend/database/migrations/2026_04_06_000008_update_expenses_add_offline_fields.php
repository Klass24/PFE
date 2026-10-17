<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Champs offline-first
            $table->timestamp('date_saisie_reelle')->nullable()->after('notes');
            $table->timestamp('date_reception_serveur')->nullable()->after('date_saisie_reelle');
            $table->string('device_id', 100)->nullable()->after('date_reception_serveur');
            $table->enum('sync_status', ['synced', 'pending'])->default('synced')->after('device_id');
        });

        // Ajouter 'weighing' (pesage) à l'enum type via SQL brut
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE expenses MODIFY COLUMN type ENUM('fuel','toll','repair','parking','weighing','other') NOT NULL"
            );
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['date_saisie_reelle', 'date_reception_serveur', 'device_id', 'sync_status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE expenses MODIFY COLUMN type ENUM('fuel','toll','repair','parking','other') NOT NULL"
            );
        }
    }
};
