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
        // ── Settlements ─────────────────────────────────────────────
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->string('settlement_reference', 100);
            $table->date('settlement_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('total_order_amount', 12, 2)->default(0);
            $table->decimal('total_shipping_fee', 10, 2)->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->decimal('total_tcs', 10, 2)->default(0);
            $table->decimal('total_tds', 10, 2)->default(0);
            $table->decimal('total_penalty', 10, 2)->default(0);
            $table->decimal('total_other_deductions', 10, 2)->default(0);
            $table->decimal('net_payable', 12, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->string('status', 30)->default('imported'); // imported, processing, reconciled, partially_reconciled, disputed, closed
            $table->timestamp('imported_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_hash')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('marketplace_account_id');
            $table->index('settlement_reference');
            $table->index('status');
            $table->index('settlement_date');
            $table->unique(['settlement_reference', 'marketplace_account_id']);
        });

        // ── Settlement Lines ────────────────────────────────────────
        Schema::create('settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('marketplace_order_id', 100);
            $table->string('sub_order_id', 100)->nullable();
            $table->string('product_name')->nullable();
            $table->string('sku_code')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('marketplace_commission', 10, 2)->default(0);
            $table->decimal('tcs_amount', 10, 2)->default(0);
            $table->decimal('tds_amount', 10, 2)->default(0);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->string('settlement_type', 30)->default('sale'); // sale, return, adjustment, penalty, compensation, other
            $table->string('match_status', 30)->default('unmatched'); // matched, unmatched, disputed, ignored
            $table->text('match_notes')->nullable();
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('order_id');
            $table->index('marketplace_order_id');
            $table->index('match_status');
        });

        // ── Payments ────────────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->string('payment_type', 30); // marketplace_settlement, customer_cod, refund, advance, other
            $table->string('payment_method', 30); // bank_transfer, upi, neft, rtgs, cheque, cash, other
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('reference_number')->nullable();
            $table->string('bank_reference')->nullable();
            $table->date('paid_at');
            $table->string('status', 30)->default('pending'); // pending, received, confirmed, bounced, reversed
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('settlement_id');
            $table->index('payment_type');
            $table->index('status');
            $table->index('paid_at');
        });

        // ── GST Entries ─────────────────────────────────────────────
        Schema::create('gst_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('gst_type', 30); // sale, purchase, credit_note, debit_note
            $table->string('place_of_supply', 10)->nullable();
            $table->string('hsn_code', 20);
            $table->decimal('taxable_amount', 10, 2);
            $table->decimal('cgst_rate', 4, 2)->default(0);
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_rate', 4, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_rate', 4, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('total_gst', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('financial_period_id')->nullable()->constrained('financial_periods')->nullOnDelete();
            $table->string('status', 30)->default('draft'); // draft, filed, amended, cancelled
            $table->timestamps();

            $table->index('company_id');
            $table->index('order_id');
            $table->index('gst_type');
            $table->index('hsn_code');
            $table->index('invoice_date');
            $table->index('status');
        });

        // ── TCS/TDS Records ─────────────────────────────────────────
        Schema::create('tcs_tds_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('record_type', 10); // tcs, tds
            $table->foreignId('marketplace_account_id')->nullable()->constrained('marketplace_accounts')->nullOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->string('section_code', 30); // e.g., '206C(1H)' for TCS, '194-O' for TDS
            $table->string('financial_year', 10); // e.g., '2026-27'
            $table->string('quarter', 5); // Q1, Q2, Q3, Q4
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('rate', 4, 2);
            $table->decimal('amount', 10, 2);
            $table->string('certificate_number')->nullable();
            $table->date('certificate_date')->nullable();
            $table->string('status', 30)->default('computed'); // computed, filed, certificate_received
            $table->timestamps();

            $table->index('company_id');
            $table->index('record_type');
            $table->index('marketplace_account_id');
            $table->index('financial_year');
            $table->index('quarter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcs_tds_records');
        Schema::dropIfExists('gst_entries');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('settlement_lines');
        Schema::dropIfExists('settlements');
    }
};
