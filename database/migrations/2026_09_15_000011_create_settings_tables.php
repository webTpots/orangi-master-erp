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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable(); // null = global setting
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->string('key', 255);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key']);
            $table->index('company_id');
        });

        Schema::create('financial_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50)->default('open'); // open, locked, closed
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month']);
        });

        Schema::create('business_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_working_day')->default(true);
            $table->boolean('is_marketplace_holiday')->default(false);
            $table->boolean('is_warehouse_holiday')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_calendar');
        Schema::dropIfExists('financial_periods');
        Schema::dropIfExists('app_settings');
    }
};
