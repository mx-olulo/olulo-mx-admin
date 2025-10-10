<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * scopes 테이블: 다형 스코프를 정규화하여 Spatie Permission의 team_id로 사용
     * - id: Spatie의 team_id로 사용됨
     * - scope_type: 스코프 타입 (ORG, BRAND, STORE)
     * - scope_ref_id: 실제 엔터티의 PK (organizations.id, brands.id, stores.id)
     */
    public function up(): void
    {
        Schema::create('scopes', function (Blueprint $table) {
            $table->id(); // Spatie의 team_id로 사용
            $table->enum('scope_type', ['ORG', 'BRAND', 'STORE'])->comment('스코프 타입');
            $table->unsignedBigInteger('scope_ref_id')->comment('실제 엔터티 PK');
            $table->timestamps();

            // 동일한 스코프는 하나만 존재
            $table->unique(['scope_type', 'scope_ref_id'], 'unique_scope');

            // 조회 성능 최적화
            $table->index(['scope_type', 'scope_ref_id'], 'idx_scope_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scopes');
    }
};
