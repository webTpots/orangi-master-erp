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
        // ── Returns ──────────────────────────────────────────────────
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sub_order_id')->nullable()->constrained('sub_orders')->nullOnDelete();
            $table->string('return_type', 30); // customer_return, rto, exchange, replacement
            $table->string('reason_category', 30); // customer_initiated, delivery_failed, wrong_item, damaged, quality_issue, not_as_described, size_issue, other
            $table->text('reason_detail')->nullable();
            $table->string('status', 30)->default('initiated'); // initiated, in_transit, received, inspecting, inspection_complete, restocked, rejected, disposed, claim_filed, claim_settled, closed
            $table->string('marketplace_return_id', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('courier_name', 50)->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('return_address_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('order_id');
            $table->index('return_type');
            $table->index('status');
            $table->index('marketplace_return_id');
        });

        // ── Return Inspections ────────────────────────────────────────
        Schema::create('return_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('inspected_by')->constrained('users')->restrictOnDelete();
            $table->string('condition', 30); // like_new, good, minor_damage, major_damage, unsellable, missing
            $table->boolean('is_resellable')->default(false);
            $table->text('inspection_notes')->nullable();
            $table->json('photos_json')->nullable();
            $table->timestamp('inspected_at');
            $table->timestamp('created_at')->useCurrent();
        });

        // ── Return Items ──────────────────────────────────────────────
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('skus')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('condition', 30)->default('like_new'); // like_new, good, minor_damage, major_damage, unsellable, missing
            $table->integer('restock_quantity')->default(0);
            $table->integer('dispose_quantity')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        // ── Claims ────────────────────────────────────────────────────
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('return_id')->nullable()->constrained('returns')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('claim_type', 30); // rto_shipping, damaged_in_transit, lost_in_transit, wrong_delivery, marketplace_penalty, weight_discrepancy, other
            $table->string('claim_against', 30); // courier, marketplace, customer, insurance
            $table->string('status', 30)->default('draft'); // draft, filed, under_review, approved, partially_approved, rejected, settled, closed
            $table->string('reference_number', 100)->nullable();
            $table->decimal('claimed_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('settled_amount', 10, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->timestamp('filed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('evidence_json')->nullable();
            $table->text('notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('return_id');
            $table->index('order_id');
            $table->index('claim_type');
            $table->index('status');
        });

        // ── Claim Communications ──────────────────────────────────────
        Schema::create('claim_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->string('direction', 20); // outgoing, incoming
            $table->string('channel', 20); // email, phone, portal, chat
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->json('attachments_json')->nullable();
            $table->timestamp('communicated_at');
            $table->foreignId('communicated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_communications');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_inspections');
        Schema::dropIfExists('returns');
    }
};
