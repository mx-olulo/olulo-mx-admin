<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_type');
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('scope_type');
            $table->string('role_key')->nullable();
            $table->boolean('is_owner')->default(false);
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tenant_type', 'tenant_id'], 'tenant_memberships_unique');
            $table->index(['tenant_type', 'tenant_id'], 'tenant_memberships_tenant_idx');
            $table->index(['scope_type', 'user_id'], 'tenant_memberships_scope_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_memberships');
    }
};
