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
        Schema::create('vendor_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('token');
        });

        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('sku_id')->nullable()->constrained('skus')->nullOnDelete();
            $table->foreignId('design_id')->nullable()->constrained('designs')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('rule_type', 30); // min_stock, days_of_stock, forecast_based, manual
            $table->integer('min_stock_threshold')->nullable();
            $table->integer('days_of_stock_threshold')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->integer('max_order_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('rule_type');
        });

        Schema::create('reorder_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('skus')->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->integer('current_stock');
            $table->decimal('daily_run_rate', 6, 2);
            $table->integer('days_until_stockout');
            $table->integer('suggested_quantity');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->string('priority', 20); // low, medium, high, urgent
            $table->string('status', 30)->default('pending'); // pending, approved, converted_to_po, dismissed
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('priority');
            $table->index('status');
        });

        Schema::create('marketplace_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('config_key');
            $table->text('config_value');
            $table->timestamps();

            $table->unique(['marketplace_id', 'company_id', 'config_key'], 'mktplace_company_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_configs');
        Schema::dropIfExists('reorder_suggestions');
        Schema::dropIfExists('reorder_rules');
        Schema::dropIfExists('vendor_portal_tokens');
    }
};
