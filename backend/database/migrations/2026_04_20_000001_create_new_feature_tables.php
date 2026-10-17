<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Maintenance Schedules ──────────────────────────────
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('maintenance_type', ['oil_change', 'tire_rotation', 'inspection', 'engine_service', 'brake_check', 'filter_change', 'other']);
            $table->integer('frequency_km')->nullable();
            $table->integer('frequency_months')->nullable();
            $table->date('last_service_date')->nullable();
            $table->integer('last_service_km')->nullable();
            $table->date('next_due_date')->nullable();
            $table->integer('next_due_km')->nullable();
            $table->enum('status', ['active', 'completed', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'vehicle_id']);
            $table->index('next_due_date');
        });

        // ── Maintenance Records ────────────────────────────────
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('maintenance_schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('service_date');
            $table->integer('service_km');
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('provider')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });

        // ── Fuel Entries ───────────────────────────────────────
        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('mission_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('liters', 7, 2);
            $table->decimal('cost_per_liter', 5, 2);
            $table->decimal('total_cost', 10, 2);
            $table->integer('odometer_reading');
            $table->string('station_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('recorded_at');
            $table->enum('sync_status', ['synced', 'pending'])->default('synced');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'vehicle_id']);
            $table->index('recorded_at');
        });

        // ── Fuel Anomalies ─────────────────────────────────────
        Schema::create('fuel_anomalies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('mission_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('anomaly_type', ['excessive_consumption', 'potential_theft', 'gauge_malfunction']);
            $table->decimal('expected_consumption', 7, 2);
            $table->decimal('actual_consumption', 7, 2);
            $table->decimal('deviation_percent', 5, 2);
            $table->enum('status', ['unresolved', 'confirmed', 'false_positive'])->default('unresolved');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        // ── Tracking Points ────────────────────────────────────
        Schema::create('tracking_points', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('mission_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->decimal('speed', 5, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['mission_id', 'recorded_at']);
            $table->index(['company_id']);
        });

        // ── Geofences ──────────────────────────────────────────
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['circle', 'polygon'])->default('circle');
            $table->decimal('center_lat', 10, 8)->nullable();
            $table->decimal('center_lng', 11, 8)->nullable();
            $table->integer('radius_meters')->nullable();
            $table->json('polygon_points')->nullable();
            $table->enum('trigger_on', ['enter', 'exit', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });

        // ── Alert Rules ────────────────────────────────────────
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', [
                'mission_completed', 'expense_created', 'incident_reported',
                'document_expiring', 'fuel_anomaly', 'geofence_breach',
                'maintenance_due', 'speed_exceeded',
            ]);
            $table->json('conditions');
            $table->json('actions');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('alert_rule_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->onDelete('cascade');
            $table->string('triggered_by_type');
            $table->unsignedBigInteger('triggered_by_id');
            $table->json('execution_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rule_executions');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('geofences');
        Schema::dropIfExists('tracking_points');
        Schema::dropIfExists('fuel_anomalies');
        Schema::dropIfExists('fuel_entries');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('maintenance_schedules');
    }
};
