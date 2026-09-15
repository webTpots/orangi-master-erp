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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->string('po_number', 20);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('status', 30)->default('draft'); // draft, sent, partially_confirmed, confirmed, partially_received, received, closed, cancelled
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'po_number']);
            $table->index('vendor_id');
            $table->index('status');
            $table->index('order_date');
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('skus')->restrictOnDelete();
            $table->integer('quantity_requested');
            $table->integer('quantity_confirmed')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 10, 4);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, confirmed, partial, received, cancelled
            $table->text('vendor_notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('sku_id');
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('receipt_number', 30);
            $table->date('receipt_date');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('receipt_number');
            $table->index('purchase_order_id');
            $table->index('warehouse_id');
            $table->index('receipt_date');
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('po_line_id')->constrained('purchase_order_lines')->restrictOnDelete();
            $table->foreignId('sku_id')->constrained('skus')->restrictOnDelete();
            $table->integer('quantity_received');
            $table->integer('quantity_accepted')->default(0);
            $table->integer('quantity_rejected')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->decimal('unit_cost', 10, 4);
            $table->timestamps();

            $table->index('goods_receipt_id');
            $table->index('po_line_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
