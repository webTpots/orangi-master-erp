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
        // ── Orders ──────────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->restrictOnDelete();
            $table->foreignId('marketplace_account_id')->nullable()->constrained('marketplace_accounts')->nullOnDelete();
            $table->string('marketplace_order_id', 50);
            $table->date('order_date');
            $table->time('order_time')->nullable();
            $table->date('business_cycle_date')->nullable();
            $table->time('cutoff_time')->nullable();
            $table->string('status', 30)->default('new'); // new, accepted, label_ready, picking, packed, ready_to_ship, scanned, handed_over, in_transit, delivered, cancelled, return, rto
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_city', 100)->nullable();
            $table->string('customer_state', 100)->nullable();
            $table->string('customer_pincode', 10)->nullable();
            $table->string('payment_type', 20)->nullable(); // prepaid, cod
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('invoice_number', 50)->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('taxable_value', 12, 2)->nullable();
            $table->decimal('sgst', 10, 2)->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->decimal('other_charges', 10, 2)->nullable();
            $table->string('courier_partner', 50)->nullable();
            $table->string('awb_number', 50)->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->string('fulfillment_status', 30)->nullable();
            $table->date('sla_date')->nullable();
            $table->boolean('is_sla_risk')->default(false);
            $table->text('notes')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'marketplace_id', 'marketplace_order_id'], 'orders_company_marketplace_order_unique');
            $table->index('marketplace_order_id');
            $table->index('status');
            $table->index('order_date');
            $table->index('courier_partner');
            $table->index('awb_number');
        });

        // ── Sub-Orders ──────────────────────────────────────────────
        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('sub_order_number', 50);
            $table->foreignId('sku_id')->nullable()->constrained('skus')->nullOnDelete();
            $table->string('marketplace_sku', 100)->nullable();
            $table->string('product_name', 500)->nullable();
            $table->string('variant_description', 255)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('size', 20)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('line_total', 12, 2)->nullable();
            $table->string('stock_status', 30)->default('pending'); // pending, in_stock, partial, out_of_stock, not_mapped
            $table->string('processing_status', 30)->default('pending'); // pending, picked, packed, shipped, delivered, returned, cancelled
            $table->timestamps();

            $table->unique(['company_id', 'sub_order_number'], 'sub_orders_company_sub_order_unique');
            $table->index('order_id');
            $table->index('sku_id');
        });

        // ── Label Files ─────────────────────────────────────────────
        Schema::create('label_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->restrictOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_hash', 64)->nullable();
            $table->integer('total_pages')->default(0);
            $table->integer('parsed_labels')->default(0);
            $table->integer('linked_labels')->default(0);
            $table->integer('error_labels')->default(0);
            $table->string('status', 30)->default('uploaded'); // uploaded, processing, completed, failed
            $table->timestamps();
        });

        // ── Labels ──────────────────────────────────────────────────
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('sub_order_id')->nullable()->constrained('sub_orders')->nullOnDelete();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->restrictOnDelete();
            $table->foreignId('label_file_id')->nullable()->constrained('label_files')->nullOnDelete();
            $table->string('awb_number', 50)->nullable();
            $table->string('courier_partner', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_city', 100)->nullable();
            $table->string('customer_state', 100)->nullable();
            $table->string('customer_pincode', 10)->nullable();
            $table->string('payment_type', 20)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('size', 20)->nullable();
            $table->integer('quantity')->default(1);
            $table->string('color', 50)->nullable();
            $table->string('sub_order_number', 50)->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->decimal('taxable_value', 12, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->text('raw_text')->nullable();
            $table->integer('page_number')->nullable();
            $table->string('status', 30)->default('parsed'); // parsed, linked, error
            $table->timestamps();

            $table->index('awb_number');
            $table->index('sub_order_number');
        });

        // ── Manifests ───────────────────────────────────────────────
        Schema::create('manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->restrictOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_hash', 64)->nullable();
            $table->date('manifest_date')->nullable();
            $table->string('supplier_name', 255)->nullable();
            $table->string('status', 30)->default('uploaded'); // uploaded, processing, completed, failed
            $table->timestamps();
        });

        // ── Manifest Picklist Lines ─────────────────────────────────
        Schema::create('manifest_picklist_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained('manifests')->cascadeOnDelete();
            $table->string('sku', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('size', 20)->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        // ── Manifest Shipment Lines ─────────────────────────────────
        Schema::create('manifest_shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained('manifests')->cascadeOnDelete();
            $table->string('courier', 50)->nullable();
            $table->string('supplier', 100)->nullable();
            $table->integer('serial_number')->nullable();
            $table->string('sub_order_number', 50)->nullable();
            $table->string('awb', 50)->nullable();
            $table->string('sku', 100)->nullable();
            $table->integer('quantity')->default(1);
            $table->string('size', 20)->nullable();
            $table->string('packed_status', 30)->default('unknown'); // unknown, not_packed, packed, partially_packed
            $table->timestamps();
        });

        // ── Order Status History ────────────────────────────────────
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('manifest_shipment_lines');
        Schema::dropIfExists('manifest_picklist_lines');
        Schema::dropIfExists('manifests');
        Schema::dropIfExists('labels');
        Schema::dropIfExists('label_files');
        Schema::dropIfExists('sub_orders');
        Schema::dropIfExists('orders');
    }
};
