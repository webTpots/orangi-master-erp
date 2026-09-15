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
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('hsn_code', 10)->nullable();
            $table->decimal('gst_rate', 5, 2)->nullable();
            $table->string('primary_image_path', 500)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index('company_id');
            $table->index('status');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('designs')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('material', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('company_id');
            $table->index('design_id');
        });

        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('color', 50)->nullable();
            $table->string('size', 20)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->string('primary_image_path', 500)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('product_id');
            $table->index('company_id');
        });

        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('sku_code', 100);
            $table->string('barcode', 50)->nullable();
            $table->string('master_sku_code', 100)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('cost_price', 10, 4)->nullable();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->integer('minimum_stock_level')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'sku_code']);
            $table->index('sku_code');
            $table->index('barcode');
            $table->index('master_sku_code');
            $table->index('variant_id');
        });

        Schema::create('sku_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('skus')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('external_identifier', 255);
            $table->string('external_name', 255)->nullable();
            $table->string('source_type', 50); // marketplace or vendor
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('marketplace_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('mapping_status', 50)->default('suggested'); // confirmed, suggested, rejected
            $table->unsignedBigInteger('mapped_by')->nullable();
            $table->timestamps();

            $table->index('external_identifier');
            $table->index('sku_id');
            $table->index('company_id');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imageable_id');
            $table->string('imageable_type');
            $table->string('image_path', 500);
            $table->string('image_type', 50)->default('gallery'); // primary, gallery, vendor, marketplace
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['imageable_id', 'imageable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('sku_mappings');
        Schema::dropIfExists('skus');
        Schema::dropIfExists('variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('designs');
    }
};
