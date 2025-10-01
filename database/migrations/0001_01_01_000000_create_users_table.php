<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users 테이블 생성 마이그레이션
     *
     * Firebase 인증, Two-Factor 인증, 다국어 지원 등
     * 모든 사용자 관련 필드를 포함하는 통합 마이그레이션
     */
    public function up(): void
    {
        // Users 테이블 생성
        Schema::create('users', function (Blueprint $table) {
            // 기본 정보
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // 추가 사용자 정보
            $table->string('phone_number')->nullable()
                ->comment('사용자가 입력한 전화번호');
            $table->string('avatar_url')->nullable()
                ->comment('프로필 이미지 URL');

            // 인증 정보
            $table->string('password');

            // Two-Factor 인증 필드
            $table->text('two_factor_secret')->nullable()
                ->comment('2FA 시크릿 키');
            $table->text('two_factor_recovery_codes')->nullable()
                ->comment('2FA 복구 코드');
            $table->timestamp('two_factor_confirmed_at')->nullable()
                ->comment('2FA 활성화 시간');

            // Firebase 인증 관련 필드
            $table->string('firebase_uid')->nullable()->unique()
                ->comment('Firebase Authentication UID');
            $table->string('provider')->nullable()
                ->comment('Authentication provider (google, email, phone, etc.)');
            $table->json('firebase_claims')->nullable()
                ->comment('Firebase custom claims');
            $table->string('firebase_phone')->nullable()
                ->comment('Firebase 인증에서 가져온 전화번호');

            // 지역화 및 세션 관련
            $table->string('locale', 10)->default('es-MX')
                ->comment('사용자 선호 언어 (es-MX, en-US, ko-KR)');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable()
                ->comment('마지막 로그인 시간');

            // 타임스탬프
            $table->timestamps();

            // 인덱스 추가 (성능 최적화)
            $table->index('firebase_uid', 'users_firebase_uid_index');
            $table->index('phone_number', 'users_phone_number_index');
            $table->index('firebase_phone', 'users_firebase_phone_index');
            $table->index('locale', 'users_locale_index');
            $table->index(['last_login_at', 'locale'], 'users_last_login_locale_index');
        });

        // Password Reset Tokens 테이블
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions 테이블 (Sanctum SPA 세션용)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
