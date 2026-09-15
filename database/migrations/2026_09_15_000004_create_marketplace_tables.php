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
        Schema::create('marketplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20); // meesho, flipkart, amazon
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // cutoff_time, sla_days, label_format, manifest_format, settlement_format, return_format, api_config
            $table->timestamps();

            $table->index('company_id');
            $table->index('code');
        });

        Schema::create('marketplace_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('account_name', 200);
            $table->string('account_id', 100)->nullable();
            $table->text('credentials')->nullable(); // encrypted
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('marketplace_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_accounts');
        Schema::dropIfExists('marketplaces');
    }
};
