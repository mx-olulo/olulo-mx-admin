<?php

declare(strict_types=1);

namespace App\Filament\Organization\Pages;

use App\Models\Organization;
use App\Models\User;
use App\Services\OnboardingService;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @CODE:ONBOARD-001 | SPEC: .moai/specs/SPEC-ONBOARD-001/spec.md | TEST: tests/Feature/OnboardingServiceTest.php
 *
 * 조직 온보딩 위자드: 신규 사용자가 조직을 생성하고 owner role을 부여받습니다.
 *
 * Filament V4 Tenancy RegisterTenant 기반 구현:
 * - form(Schema): 조직 생성 폼
 * - handleRegistration(array): Organization 생성 (OnboardingService 위임)
 */
class OnboardingWizard extends RegisterTenant
{
    /**
     * 네비게이션에 표시하지 않음 (온보딩 페이지는 자동 리디렉션만)
     */
    protected static bool $shouldRegisterNavigation = false;

    /**
     * 테넌트가 없어도 접근 가능 (온보딩 시나리오)
     */
    protected static bool $requiresTenancy = false;

    /**
     * 페이지 레이블 (브라우저 탭/헤더)
     */
    public static function getLabel(): string
    {
        return '조직 온보딩';
    }

    /**
     * Filament V4: form() 메서드로 폼 스키마 정의
     *
     * @param  Schema  $schema  Filament Schema 인스턴스
     * @return Schema 구성된 스키마
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('조직 이름')
                    ->required()
                    ->maxLength(255)
                    ->helperText('조직의 공식 명칭을 입력하세요')
                    ->unique(table: 'organizations', column: 'name'),
            ]);
    }

    /**
     * 테넌트 등록 가능 여부 확인
     *
     * 온보딩 시나리오: 모든 인증된 사용자가 조직 생성 가능
     *
     * @return bool 항상 true (모든 인증된 사용자 허용)
     */
    public static function canRegisterTenant(): bool
    {
        return true;
    }

    /**
     * 테넌트 등록 처리 (Filament Tenancy 생명주기 메서드)
     *
     * @param  array<string, mixed>  $data  폼 데이터
     * @return Model 생성된 테넌트 모델 (Organization)
     *
     * @throws \Exception 인증 실패
     */
    protected function handleRegistration(array $data): Model
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new \Exception('User must be authenticated');
        }

        $onboardingService = app(OnboardingService::class);

        // 조직 생성
        return $onboardingService->createOrganization($user, ['name' => $data['name']]);
    }
}
