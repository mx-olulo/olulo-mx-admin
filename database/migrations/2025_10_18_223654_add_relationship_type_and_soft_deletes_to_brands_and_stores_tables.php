<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Brand 및 Store 테이블에 relationship_type과 soft deletes 추가
 *
 * - relationship_type: 직영(owned) vs 입점(tenant) 구분
 * - deleted_at: Soft Delete 지원
 */

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
        // brands 테이블에 컬럼 추가
        Schema::table('brands', function (Blueprint $table) {
            $table->enum('relationship_type', ['owned', 'tenant'])
                ->default('owned')
                ->after('organization_id')
                ->comment('직영(owned) 또는 입점(tenant) 관계 구분');

            $table->softDeletes()->after('is_active');
        });

        // stores 테이블에 컬럼 추가
        Schema::table('stores', function (Blueprint $table) {
            $table->enum('relationship_type', ['owned', 'tenant'])
                ->default('owned')
                ->after('organization_id')
                ->comment('직영(owned) 또는 입점(tenant) 관계 구분');

            $table->softDeletes()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('relationship_type');
            $table->dropSoftDeletes();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('relationship_type');
            $table->dropSoftDeletes();
        });
    }
};
