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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->after('organization_id')->constrained('companies')->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('role', 50)->nullable()->after('phone');
            $table->string('status', 50)->default('active')->after('role');
            $table->string('avatar_path', 500)->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path');

            $table->index('organization_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['company_id']);
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn([
                'organization_id',
                'company_id',
                'phone',
                'role',
                'status',
                'avatar_path',
                'last_login_at',
            ]);
        });
    }
};
