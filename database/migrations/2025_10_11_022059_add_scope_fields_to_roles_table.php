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
        Schema::table('roles', function (Blueprint $table) {
            // 스코프 타입 (ORG, BRAND, STORE)
            $table->string('scope_type', 20)->nullable()->after('team_id')->comment('스코프 타입');
            
            // 실제 엔터티 PK
            $table->unsignedBigInteger('scope_ref_id')->nullable()->after('scope_type')->comment('실제 엔터티 PK');
            
            // 조회 성능 최적화
            $table->index(['scope_type', 'scope_ref_id'], 'idx_role_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('idx_role_scope');
            $table->dropColumn(['scope_type', 'scope_ref_id']);
        });
    }
};
