<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── Shipments ─────────────────────────────────────────────────
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sub_order_id')->nullable()->constrained('sub_orders')->nullOnDelete();
            $table->string('tracking_number', 100);
            $table->string('awb_number', 50)->nullable();
            $table->string('courier_name', 50);
            $table->string('courier_code', 30)->nullable();
            $table->string('status', 30)->default('created'); // created, picked_up, in_transit, out_for_delivery, delivered, rto_initiated, rto_in_transit, rto_delivered, lost, damaged
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('rto_delivered_at')->nullable();
            $table->integer('weight_grams')->nullable();
            $table->json('dimensions_json')->nullable();
            $table->json('pickup_address_json')->nullable();
            $table->json('delivery_address_json')->nullable();
            $table->timestamp('last_scan_at')->nullable();
            $table->string('last_scan_location', 255)->nullable();
            $table->string('last_scan_status', 50)->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'tracking_number'], 'shipments_company_tracking_unique');
            $table->index('company_id');
            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('status');
            $table->index('courier_name');
        });

        // ── Shipment Scans (immutable scan history) ───────────────────
        Schema::create('shipment_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('scan_type', 30); // pickup, in_transit, hub, out_for_delivery, delivered, rto_initiated, rto_pickup, rto_in_transit, rto_delivered, exception, damaged, lost
            $table->timestamp('scanned_at');
            $table->string('location', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('status_code', 50)->nullable();
            $table->text('status_description')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('created_at')->nullable(); // no updated_at — immutable

            $table->index('shipment_id');
            $table->index('scan_type');
            $table->index('scanned_at');
        });

        // ── Scan Logs (barcode/QR scanning at warehouse) ──────────────
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('scannable'); // scannable_type, scannable_id
            $table->string('scan_type', 30); // pack, dispatch, receive, return_receive, quality_check, shelf_assign
            $table->string('barcode_data', 255);
            $table->string('scan_method', 20)->default('barcode'); // barcode, qr, manual
            $table->timestamp('scanned_at');
            $table->string('location', 255)->nullable(); // warehouse zone/rack/bin
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable(); // no updated_at — immutable

            $table->index('company_id');
            // morphs() already creates scannable_type + scannable_id index
            $table->index('scan_type');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
        Schema::dropIfExists('shipment_scans');
        Schema::dropIfExists('shipments');
    }
};
