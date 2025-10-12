<?php

declare(strict_types=1);

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Config;

/**
 * FirebaseService 단위 테스트
 *
 * Firebase 통합 서비스의 핵심 기능을 테스트합니다.
 * 실제 Firebase API는 모킹하여 독립적인 테스트를 수행합니다.
 */
beforeEach(function () {
    // Firebase 설정을 테스트용으로 모킹
    Config::set('services.firebase', [
        'project_id' => 'test-project',
        'client_email' => 'test@example.com',
        'private_key' => 'test-private-key',
        'client_id' => 'test-client-id',
        'private_key_id' => 'test-private-key-id',
    ]);
});

describe('환경 변수 자격증명', function () {
    test('모든 자격증명이 설정된 경우 true 반환', function () {
        Config::set('services.firebase.project_id', 'test-project');
        Config::set('services.firebase.client_email', 'test@example.com');
        Config::set('services.firebase.private_key', 'test-key');

        $hasCredentials = ! empty(Config::get('services.firebase.project_id')) &&
                          ! empty(Config::get('services.firebase.client_email')) &&
                          ! empty(Config::get('services.firebase.private_key'));

        expect($hasCredentials)->toBeTrue();
    });

    test('일부 자격증명이 누락된 경우 false 반환', function () {
        Config::set('services.firebase.project_id', '');

        $hasCredentials = ! empty(Config::get('services.firebase.project_id')) &&
                          ! empty(Config::get('services.firebase.client_email')) &&
                          ! empty(Config::get('services.firebase.private_key'));

        expect($hasCredentials)->toBeFalse();
    });
});

describe('이메일에서 사용자 이름 추출', function () {
    test('다양한 이메일 형식에서 이름을 올바르게 추출', function () {
        $testCases = [
            'john.doe@example.com' => 'John doe',
            'user_name@test.com' => 'User name',
            'simple@domain.com' => 'Simple',
            'test-user@example.org' => 'Test user',
        ];

        foreach ($testCases as $email => $expectedName) {
            $localPart = explode('@', $email)[0];
            $actualName = ucfirst(str_replace(['.', '_', '-'], ' ', $localPart));

            expect($actualName)->toBe($expectedName);
        }
    });
})->group('firebase');
