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
        Schema::create('exception_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('default_severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('default_assignee_role', 50)->nullable();
            $table->unsignedInteger('sla_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add category_id to existing exceptions table
        Schema::table('exceptions', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('company_id')
                ->constrained('exception_categories')->nullOnDelete();
        });

        Schema::create('exception_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exception_id')->constrained('exceptions')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->string('status', 30)->default('assigned'); // assigned, in_progress, escalated, resolved, closed
            $table->string('priority', 20)->default('medium');  // low, medium, high, urgent
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['exception_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('exception_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exception_id')->constrained('exceptions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('exception_id');
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->string('channel', 30); // in_app, email, whatsapp, sms
            $table->string('subject', 255)->nullable();
            $table->text('body_template');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('channel');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('notification_type', 100);
            $table->boolean('channel_in_app')->default(true);
            $table->boolean('channel_email')->default(false);
            $table->boolean('channel_whatsapp')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('exception_comments');
        Schema::dropIfExists('exception_assignments');

        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('exception_categories');
    }
};
