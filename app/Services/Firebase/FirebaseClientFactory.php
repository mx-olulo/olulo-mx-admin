<?php

declare(strict_types=1);

namespace App\Services\Firebase;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;

/**
 * Firebase 클라이언트 팩토리
 *
 * Firebase Admin SDK의 다양한 서비스 인스턴스를 생성하고 관리하는 팩토리 클래스입니다.
 * 환경 변수 또는 서비스 어카운트 키 파일을 통한 초기화를 지원하며,
 * 각 서비스별로 지연 로딩(lazy loading)을 통해 성능을 최적화합니다.
 *
 * 주요 기능:
 * - Firebase Factory 인스턴스 생성 및 설정
 * - Auth, Database, Messaging 서비스 인스턴스 제공
 * - 환경별 설정 (운영/개발/에뮬레이터) 지원
 * - 서비스 어카운트 자격증명 관리
 */
class FirebaseClientFactory
{
    private ?Factory $factory = null;

    private ?Auth $auth = null;

    private ?Database $database = null;

    private ?Messaging $messaging = null;

    /**
     * Firebase Auth 인스턴스 반환
     *
     * @return Auth Firebase Auth 인스턴스
     *
     * @throws Exception 초기화 실패 시
     */
    public function createAuth(): Auth
    {
        if (! $this->auth instanceof \Kreait\Firebase\Contract\Auth) {
            $this->auth = $this->getFactory()->createAuth();
        }

        return $this->auth;
    }

    /**
     * Firebase Database 인스턴스 반환
     *
     * @return Database Firebase Realtime Database 인스턴스
     *
     * @throws Exception 초기화 실패 시
     */
    public function createDatabase(): Database
    {
        if (! $this->database instanceof \Kreait\Firebase\Contract\Database) {
            $this->database = $this->getFactory()->createDatabase();
        }

        return $this->database;
    }

    /**
     * Firebase Messaging 인스턴스 반환
     *
     * @return Messaging Firebase Cloud Messaging 인스턴스
     *
     * @throws Exception 초기화 실패 시
     */
    public function createMessaging(): Messaging
    {
        if (! $this->messaging instanceof \Kreait\Firebase\Contract\Messaging) {
            $this->messaging = $this->getFactory()->createMessaging();
        }

        return $this->messaging;
    }

    /**
     * Firebase Factory 인스턴스 반환
     *
     * 지연 로딩을 통해 Factory를 초기화하고 반환합니다.
     *
     * @return Factory Firebase Factory 인스턴스
     *
     * @throws Exception 초기화 실패 시
     */
    private function getFactory(): Factory
    {
        if (! $this->factory instanceof \Kreait\Firebase\Factory) {
            $this->factory = $this->initializeFactory();
        }

        return $this->factory;
    }

    /**
     * Firebase Factory 초기화
     *
     * 환경 변수 또는 서비스 어카운트 키 파일을 통해 Firebase Factory를 초기화합니다.
     * 환경 변수 설정을 우선적으로 확인하고, 없을 경우 키 파일을 사용합니다.
     *
     * @return Factory 초기화된 Firebase Factory
     *
     * @throws Exception 초기화 실패 시
     */
    private function initializeFactory(): Factory
    {
        try {
            $factory = new Factory;

            // 환경 변수를 통한 설정 우선 시도
            if ($this->hasEnvironmentCredentials()) {
                $factory = $factory->withServiceAccount($this->getServiceAccountFromEnvironment());
                Log::info('Firebase Factory: 환경 변수 기반 초기화 완료');
            } else {
                // 서비스 어카운트 키 파일 사용
                $serviceAccountPath = $this->getServiceAccountFilePath();
                if (! file_exists($serviceAccountPath)) {
                    throw new Exception('Firebase 서비스 어카운트 키 파일이 존재하지 않습니다: ' . $serviceAccountPath);
                }

                $factory = $factory->withServiceAccount($serviceAccountPath);
                Log::info('Firebase Factory: 서비스 어카운트 파일 기반 초기화 완료', [
                    'file_path' => $serviceAccountPath,
                ]);
            }

            return $factory;
        } catch (Exception $e) {
            Log::error('Firebase Factory 초기화 실패: ' . $e->getMessage());

            throw new Exception('Firebase 서비스 초기화에 실패했습니다: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 환경 변수에 Firebase 자격증명이 설정되어 있는지 확인
     *
     * @return bool 환경 변수 설정 여부
     */
    private function hasEnvironmentCredentials(): bool
    {
        return ! empty(Config::get('firebase.project_id')) &&
               ! empty(Config::get('firebase.client_email')) &&
               ! empty(Config::get('firebase.private_key'));
    }

    /**
     * 환경 변수에서 서비스 어카운트 설정 배열 생성
     *
     * @return array<string, string> 서비스 어카운트 설정 배열
     */
    private function getServiceAccountFromEnvironment(): array
    {
        return [
            'type' => 'service_account',
            'project_id' => Config::get('firebase.project_id'),
            'private_key_id' => Config::get('firebase.private_key_id'),
            'private_key' => str_replace('\\n', "\n", Config::get('firebase.private_key')),
            'client_email' => Config::get('firebase.client_email'),
            'client_id' => Config::get('firebase.client_id'),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => sprintf(
                'https://www.googleapis.com/robot/v1/metadata/x509/%s',
                urlencode((string) Config::get('firebase.client_email'))
            ),
        ];
    }

    /**
     * 서비스 어카운트 키 파일 경로 반환
     *
     * @return string 서비스 어카운트 키 파일의 절대 경로
     */
    private function getServiceAccountFilePath(): string
    {
        return resource_path('firebase/mx-olulo-firebase-adminsdk-fbsvc-417ad72871.json');
    }

    /**
     * 팩토리 인스턴스 재설정
     *
     * 테스트 또는 설정 변경 시 팩토리를 재초기화하기 위해 사용합니다.
     */
    public function reset(): void
    {
        $this->factory = null;
        $this->auth = null;
        $this->database = null;
        $this->messaging = null;

        Log::info('Firebase Factory 인스턴스가 재설정되었습니다');
    }
}
