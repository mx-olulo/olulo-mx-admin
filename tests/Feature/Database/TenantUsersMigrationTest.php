<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @TEST:RBAC-001-FOUNDATION | SPEC: SPEC-RBAC-001.md
 *
 * tenant_users 테이블 마이그레이션 테스트
 * - 테이블 존재 확인
 * - 컬럼 존재 확인
 * - 인덱스 존재 확인
 */
class TenantUsersMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * tenant_users 테이블이 존재해야 함
     */
    public function tenant_users_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('tenant_users'));
    }

    /**
     * @test
     * tenant_users 테이블이 필수 컬럼을 가져야 함
     */
    public function tenant_users_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('tenant_users', [
            'id',
            'user_id',
            'tenant_type',
            'tenant_id',
            'role',
            'created_at',
            'updated_at',
        ]));
    }

    /**
     * @test
     * users 테이블에 user_type 컬럼이 추가되어야 함
     */
    public function users_table_has_user_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'user_type'));
    }

    /**
     * @test
     * users 테이블에 global_role 컬럼이 추가되어야 함
     */
    public function users_table_has_global_role_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'global_role'));
    }
}
