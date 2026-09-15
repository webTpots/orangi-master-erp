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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->string('slug')->unique();
            $table->string('logo_path', 500)->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('legal_name', 255)->nullable();
            $table->string('gstin', 15)->nullable()->unique();
            $table->string('pan', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('code', 20)->unique();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });

        Schema::create('warehouse_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('code', 20);
            $table->timestamps();

            $table->unique(['zone_id', 'code']);
        });

        Schema::create('warehouse_bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('warehouse_racks')->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('code', 20);
            $table->timestamps();

            $table->unique(['rack_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_bins');
        Schema::dropIfExists('warehouse_racks');
        Schema::dropIfExists('warehouse_zones');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('organizations');
    }
};
