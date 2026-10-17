<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dateTime('planned_departure')->nullable()->after('destination');
            $table->dateTime('planned_arrival')->nullable()->after('planned_departure');
            $table->dateTime('actual_departure')->nullable()->after('planned_arrival');
            $table->dateTime('actual_arrival')->nullable()->after('actual_departure');
            $table->decimal('total_cost', 10, 2)->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn([
                'planned_departure', 'planned_arrival',
                'actual_departure', 'actual_arrival', 'total_cost',
            ]);
        });
    }
};
