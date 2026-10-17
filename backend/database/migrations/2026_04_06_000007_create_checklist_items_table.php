<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->string('item_key', 60);   // ex: tire_pressure, fire_extinguisher
            $table->string('label', 150);     // ex: "Pression des pneus"
            $table->boolean('is_ok')->default(false);
            $table->boolean('requires_photo')->default(false);
            $table->string('photo_path')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['checklist_id', 'item_key']);
            $table->index('checklist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
