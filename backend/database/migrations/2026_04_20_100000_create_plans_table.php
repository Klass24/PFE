<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 50);            // Starter, Business, Enterprise
            $table->string('slug', 50)->unique();    // starter, pro, enterprise
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 5)->default('EUR');
            $table->integer('max_vehicles')->default(5);
            $table->integer('max_drivers')->default(5);
            $table->integer('max_users')->default(3);
            $table->json('features')->nullable();     // ["Dashboard de base", "Support email"]
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('trial_days')->default(0);
            $table->timestamps();
        });

        // Ajouter plan_id FK sur companies (nullable pour rétro-compatibilité)
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan')->constrained('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });

        Schema::dropIfExists('plans');
    }
};
