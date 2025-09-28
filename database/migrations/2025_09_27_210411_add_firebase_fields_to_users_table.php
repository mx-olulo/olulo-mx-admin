<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Firebase 관련 추가 필드 마이그레이션
     *
     * 이미 존재하는 필드는 건너뛰고, 새로운 필드만 추가합니다:
     * - firebase_phone: Firebase에서 가져온 전화번호
     * - locale: 사용자 선호 언어
     * - last_login_at: 마지막 로그인 시간
     *
     * 이미 존재하는 필드:
     * - firebase_uid (2025_09_26_164012 마이그레이션)
     * - phone_number (2025_09_27_204927 마이그레이션)
     * - avatar_url (2025_09_27_204927 마이그레이션)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Firebase에서 가져온 전화번호 (phone_number와는 별개)
            // phone_number: 사용자가 직접 입력하거나 수정한 전화번호
            // firebase_phone: Firebase에서 인증된 원본 전화번호
            if (! Schema::hasColumn('users', 'firebase_phone')) {
                $table->string('firebase_phone')->nullable()->after('firebase_claims')
                    ->comment('Firebase 인증에서 가져온 전화번호');
            }

            // 사용자 선호 언어 설정 (멕시코 시장 기본값: es-MX)
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 10)->default('es-MX')->after('firebase_phone')
                    ->comment('사용자 선호 언어 (es-MX, en-US, ko-KR)');
            }

            // 마지막 로그인 시간
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token')
                    ->comment('마지막 로그인 시간');
            }

            // 인덱스 추가 (성능 최적화)
            // firebase_uid는 이미 이전 마이그레이션에서 unique 인덱스가 추가되었음

            // email 인덱스 (unique 제약이 있으므로 별도 인덱스 불필요)

            // phone_number 인덱스 (빠른 조회를 위해)
            if (! $this->indexExists('users', 'users_phone_number_index')) {
                $table->index('phone_number', 'users_phone_number_index');
            }

            // firebase_phone 인덱스 (Firebase 전화번호로 조회 시)
            if (! $this->indexExists('users', 'users_firebase_phone_index')) {
                $table->index('firebase_phone', 'users_firebase_phone_index');
            }

            // locale 인덱스 (언어별 사용자 필터링)
            if (! $this->indexExists('users', 'users_locale_index')) {
                $table->index('locale', 'users_locale_index');
            }

            // 복합 인덱스: 로그인 활동 추적용
            if (! $this->indexExists('users', 'users_last_login_locale_index')) {
                $table->index(['last_login_at', 'locale'], 'users_last_login_locale_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 인덱스 제거 (컬럼 제거 전에 수행)
            if ($this->indexExists('users', 'users_last_login_locale_index')) {
                $table->dropIndex('users_last_login_locale_index');
            }

            if ($this->indexExists('users', 'users_locale_index')) {
                $table->dropIndex('users_locale_index');
            }

            if ($this->indexExists('users', 'users_firebase_phone_index')) {
                $table->dropIndex('users_firebase_phone_index');
            }

            if ($this->indexExists('users', 'users_phone_number_index')) {
                $table->dropIndex('users_phone_number_index');
            }

            // 컬럼 제거
            $columnsToRemove = [];

            if (Schema::hasColumn('users', 'firebase_phone')) {
                $columnsToRemove[] = 'firebase_phone';
            }

            if (Schema::hasColumn('users', 'locale')) {
                $columnsToRemove[] = 'locale';
            }

            if (Schema::hasColumn('users', 'last_login_at')) {
                $columnsToRemove[] = 'last_login_at';
            }

            if (! empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }

    /**
     * 인덱스 존재 여부 확인 헬퍼 메서드
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schemaBuilder = $connection->getSchemaBuilder();
        $indexes = $schemaBuilder->getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
