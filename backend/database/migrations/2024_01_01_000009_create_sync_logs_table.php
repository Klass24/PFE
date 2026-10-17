<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 100);
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamp('last_sync_at');
            $table->enum('status', ['success', 'partial', 'failed'])->default('success');
            $table->unsignedInteger('payload_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['device_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
