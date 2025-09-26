<?php

declare(strict_types=1);

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
            // Firebase 인증 관련 필드 추가
            $table->string('firebase_uid')->nullable()->unique()->after('id')
                ->comment('Firebase Authentication UID');

            // 인덱스 추가 (빠른 조회를 위해)
            $table->index('firebase_uid');

            // 선택적: Firebase 관련 추가 정보
            $table->string('provider')->nullable()->after('firebase_uid')
                ->comment('Authentication provider (google, email, phone, etc.)');
            $table->json('firebase_claims')->nullable()->after('provider')
                ->comment('Firebase custom claims');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 인덱스 제거
            $table->dropIndex(['firebase_uid']);

            // 컬럼 제거
            $table->dropColumn([
                'firebase_uid',
                'provider',
                'firebase_claims',
            ]);
        });
    }
};
