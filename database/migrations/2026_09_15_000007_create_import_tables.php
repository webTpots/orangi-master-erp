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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('marketplace_id')->nullable();
            $table->string('file_name', 500);
            $table->string('file_path', 1000)->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 50)->default('queued'); // queued, processing, validating, reconciling, completed, failed
            $table->integer('records_total')->default(0);
            $table->integer('records_new')->default(0);
            $table->integer('records_duplicate')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_failed')->default(0);
            $table->integer('records_exception')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
            $table->index('document_type');
        });

        Schema::create('import_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->integer('row_number')->nullable();
            $table->json('raw_data')->nullable();
            $table->json('normalized_data')->nullable();
            $table->string('status', 50)->default('new'); // new, duplicate, imported, failed, exception
            $table->string('business_key', 255)->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('import_batch_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_records');
        Schema::dropIfExists('import_batches');
    }
};
