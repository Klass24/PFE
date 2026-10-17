<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('plan', ['starter', 'pro', 'enterprise']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('billing_status', ['active', 'cancelled', 'past_due', 'trial'])->default('trial');
            $table->timestamps();

            $table->index(['company_id', 'billing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
