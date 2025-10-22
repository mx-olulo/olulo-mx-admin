<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * users 테이블에 3티어 사용자 타입 컬럼 추가
 * - user_type: admin/user/customer
 * - global_role: platform_admin/system_admin (User 타입 전용)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_type', 20)->default('admin')->after('id');
            $table->string('global_role', 50)->nullable()->after('user_type');

            // 인덱스
            $table->index('user_type', 'idx_user_type');
            $table->index('global_role', 'idx_global_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('idx_user_type');
            $table->dropIndex('idx_global_role');
            $table->dropColumn(['user_type', 'global_role']);
        });
    }
};
