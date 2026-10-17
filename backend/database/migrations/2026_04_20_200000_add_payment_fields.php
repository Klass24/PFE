<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enrichir la table subscriptions pour le paiement
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('billing_status');  // stripe | paypal | manual
            $table->string('gateway_subscription_id')->nullable()->after('payment_gateway');
            $table->string('gateway_customer_id')->nullable()->after('gateway_subscription_id');
            $table->decimal('amount', 10, 2)->default(0)->after('gateway_customer_id');
            $table->string('currency', 5)->default('EUR')->after('amount');
            $table->string('billing_cycle', 20)->default('monthly')->after('currency'); // monthly | yearly
            $table->timestamp('next_billing_at')->nullable()->after('billing_cycle');
            $table->timestamp('cancelled_at')->nullable()->after('next_billing_at');
            $table->json('gateway_metadata')->nullable()->after('cancelled_at');
        });

        // Table des paiements/transactions
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway');                     // stripe | paypal
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_invoice_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5)->default('EUR');
            $table->string('status', 30);                  // succeeded | pending | failed | refunded
            $table->string('payment_method')->nullable();   // card | paypal
            $table->string('description')->nullable();
            $table->json('gateway_metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway', 'gateway_subscription_id', 'gateway_customer_id',
                'amount', 'currency', 'billing_cycle', 'next_billing_at',
                'cancelled_at', 'gateway_metadata',
            ]);
        });
    }
};
