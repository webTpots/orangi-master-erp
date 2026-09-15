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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('skus')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedBigInteger('bin_id')->nullable();
            $table->foreign('bin_id')->references('id')->on('warehouse_bins')->nullOnDelete();
            $table->integer('physical_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('available_stock')->default(0);
            $table->integer('damaged_stock')->default(0);
            $table->integer('blocked_stock')->default(0);
            $table->integer('in_transit_stock')->default(0);
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();
            $table->string('costing_method', 20)->default('weighted_avg'); // weighted_avg, fifo
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['sku_id', 'warehouse_id']);
            $table->index('company_id');
        });

        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('transaction_type', 50);
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('reference_type', 100)->nullable(); // polymorphic
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('source_document', 255)->nullable();
            $table->string('batch_id', 50)->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('sku_id');
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — immutable ledger

            $table->index('sku_id');
            $table->index('warehouse_id');
            $table->index('transaction_type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_ledger');
        Schema::dropIfExists('inventory_items');
    }
};
