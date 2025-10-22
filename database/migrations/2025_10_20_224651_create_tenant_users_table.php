<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * tenant_users 피벗 테이블 생성
 * - Admin과 Tenant(Organization, Brand, Store) 간 M:N 관계
 * - Polymorphic 관계 (tenant_type, tenant_id)
 * - 역할 정보 (role: owner/manager/viewer)
 * - UNIQUE 제약: (user_id, tenant_type, tenant_id)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tenant_type', 20); // 'ORG', 'BRD', 'STR'
            $table->unsignedBigInteger('tenant_id');
            $table->string('role', 50); // 'owner', 'manager', 'viewer'
            $table->timestamps();

            // 인덱스
            $table->index(['tenant_type', 'tenant_id'], 'idx_tenant');
            $table->index(['user_id', 'role'], 'idx_user_role');

            // UNIQUE 제약: 동일 사용자가 동일 테넌트에 중복 역할 방지
            $table->unique(['user_id', 'tenant_type', 'tenant_id'], 'unique_user_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
