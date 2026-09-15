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
        // ── Bank Accounts ─────────────────────────────────────────────
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('ifsc_code')->nullable();
            $table->string('branch')->nullable();
            $table->enum('account_type', ['current', 'savings', 'overdraft'])->default('current');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'inactive', 'closed'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'account_number']);
            $table->index('company_id');
            $table->index('status');
        });

        // ── Bank Transactions ─────────────────────────────────────────
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->text('description');
            $table->string('reference_number')->nullable();
            $table->enum('transaction_type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('running_balance', 12, 2)->nullable();
            $table->enum('category', [
                'marketplace_settlement', 'vendor_payment', 'refund', 'expense',
                'salary', 'tax', 'transfer', 'other', 'uncategorized',
            ])->default('uncategorized');
            $table->enum('match_status', [
                'unmatched', 'auto_matched', 'manually_matched', 'disputed', 'ignored',
            ])->default('unmatched');
            $table->string('matched_entity_type')->nullable();
            $table->unsignedBigInteger('matched_entity_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('import_batch_id')->nullable();
            $table->json('raw_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('bank_account_id');
            $table->index('transaction_date');
            $table->index('match_status');
            $table->index('reference_number');
            $table->index('category');
            $table->index(['matched_entity_type', 'matched_entity_id']);
        });

        // ── Bank Statement Imports ────────────────────────────────────
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_hash');
            $table->enum('format', ['csv', 'excel', 'ofx', 'pdf'])->default('csv');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('imported_records')->default(0);
            $table->integer('duplicate_records')->default(0);
            $table->enum('status', [
                'uploaded', 'processing', 'completed', 'failed', 'partially_completed',
            ])->default('uploaded');
            $table->text('error_message')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'file_hash']);
            $table->index('company_id');
            $table->index('bank_account_id');
        });

        // ── Reconciliation Rules ──────────────────────────────────────
        Schema::create('reconciliation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('match_field', ['reference_number', 'description', 'amount', 'combination']);
            $table->string('match_pattern')->nullable();
            $table->string('match_entity_type');
            $table->enum('category', [
                'marketplace_settlement', 'vendor_payment', 'refund', 'expense',
                'salary', 'tax', 'transfer', 'other',
            ]);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_match')->default(false);
            $table->timestamps();

            $table->index('company_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_rules');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
