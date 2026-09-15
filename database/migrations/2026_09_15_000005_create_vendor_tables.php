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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('contact_person', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->integer('lead_time_days')->nullable()->default(7);
            $table->decimal('rating', 3, 2)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
        });

        Schema::create('vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('skus')->cascadeOnDelete();
            $table->string('vendor_sku', 100)->nullable();
            $table->decimal('vendor_price', 10, 4);
            $table->integer('moq')->nullable()->default(1);
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();

            $table->unique(['vendor_id', 'sku_id']);
            $table->index('sku_id');
            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_products');
        Schema::dropIfExists('vendors');
    }
};
