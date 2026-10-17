<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('mission_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained()->onDelete('restrict');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['draft', 'submitted', 'validated'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->string('device_id', 100)->nullable();
            $table->timestamp('date_saisie_reelle')->nullable();
            $table->timestamp('date_reception_serveur')->nullable();
            $table->enum('sync_status', ['synced', 'pending'])->default('synced');
            $table->timestamps();

            $table->index(['company_id', 'mission_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklists');
    }
};
