<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->morphs('documentable'); // documentable_type + documentable_id
            $table->enum('type', ['insurance', 'registration', 'license', 'technical_control', 'other']);
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'documentable_type', 'documentable_id']);
            $table->index(['company_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
