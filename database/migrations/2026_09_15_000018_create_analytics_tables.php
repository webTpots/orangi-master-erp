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
        // ── Profit Snapshots ────────────────────────────────────────────
        Schema::create('profit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->date('period_date');
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('total_cost_of_goods', 14, 2)->default(0);
            $table->decimal('total_marketplace_commission', 12, 2)->default(0);
            $table->decimal('total_shipping_cost', 12, 2)->default(0);
            $table->decimal('total_returns_cost', 12, 2)->default(0);
            $table->decimal('total_penalties', 12, 2)->default(0);
            $table->decimal('total_other_expenses', 12, 2)->default(0);
            $table->decimal('gross_profit', 14, 2)->default(0);
            $table->decimal('net_profit', 14, 2)->default(0);
            $table->decimal('profit_margin', 6, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('total_units')->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->decimal('return_rate', 6, 2)->default(0);
            $table->decimal('rto_rate', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'period_type', 'period_date'], 'profit_snapshots_unique');
        });

        // ── Design Performance ──────────────────────────────────────────
        Schema::create('design_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('design_id')->constrained('designs')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_date');
            $table->integer('units_sold')->default(0);
            $table->integer('units_returned')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->decimal('profit_margin', 6, 2)->default(0);
            $table->decimal('return_rate', 6, 2)->default(0);
            $table->decimal('avg_selling_price', 10, 2)->default(0);
            $table->integer('stock_remaining')->default(0);
            $table->integer('days_of_stock')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'design_id', 'period_type', 'period_date'], 'design_performance_unique');
        });

        // ── Marketplace Performance ─────────────────────────────────────
        Schema::create('marketplace_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_date');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->decimal('commission_rate', 6, 2)->default(0);
            $table->integer('total_returns')->default(0);
            $table->decimal('return_rate', 6, 2)->default(0);
            $table->integer('total_rto')->default(0);
            $table->decimal('rto_rate', 6, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('avg_delivery_days', 4, 1)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'marketplace_account_id', 'period_type', 'period_date'], 'marketplace_performance_unique');
        });

        // ── Vendor Performance ──────────────────────────────────────────
        Schema::create('vendor_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->enum('period_type', ['monthly', 'quarterly']);
            $table->date('period_date');
            $table->integer('total_pos')->default(0);
            $table->integer('total_units_ordered')->default(0);
            $table->integer('total_units_received')->default(0);
            $table->decimal('fulfillment_rate', 6, 2)->default(0);
            $table->decimal('avg_lead_time_days', 4, 1)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('quality_return_rate', 6, 2)->default(0);
            $table->decimal('on_time_delivery_rate', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'vendor_id', 'period_type', 'period_date'], 'vendor_performance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_performance');
        Schema::dropIfExists('marketplace_performance');
        Schema::dropIfExists('design_performance');
        Schema::dropIfExists('profit_snapshots');
    }
};
