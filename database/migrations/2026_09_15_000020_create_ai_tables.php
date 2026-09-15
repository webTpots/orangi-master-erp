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
        // ── AI Tasks ─────────────────────────────────────────────────────
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('task_type', ['document_parse', 'sku_match', 'anomaly_detect', 'demand_forecast', 'image_classify', 'smart_suggest']);
            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])->default('queued');
            $table->json('input_data');
            $table->json('output_data')->nullable();
            $table->decimal('confidence_score', 4, 2)->nullable();
            $table->string('model_used')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'task_type', 'status', 'created_at'], 'ai_tasks_lookup');
        });

        // ── AI Suggestions ───────────────────────────────────────────────
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('ai_task_id')->nullable()->constrained('ai_tasks')->nullOnDelete();
            $table->enum('suggestion_type', ['sku_mapping', 'reorder', 'price_adjustment', 'quality_alert', 'process_improvement', 'anomaly']);
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->decimal('confidence', 4, 2)->default(0);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->json('action_data')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'suggestion_type', 'status', 'priority'], 'ai_suggestions_lookup');
        });

        // ── AI Training Data ─────────────────────────────────────────────
        Schema::create('ai_training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('data_type', ['sku_mapping_feedback', 'classification_correction', 'anomaly_feedback']);
            $table->json('input_data');
            $table->json('expected_output');
            $table->json('actual_output')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->text('feedback_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_training_data');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_tasks');
    }
};
