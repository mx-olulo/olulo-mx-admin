<?php

declare(strict_types=1);

/**
 * @CODE:AUTH-REDIRECT-001:DOMAIN | SPEC: .moai/specs/SPEC-AUTH-REDIRECT-001/spec.md
 *
 * 인증 후 지능형 테넌트 리다이렉트 서비스
 *
 * TDD History:
 * - REFACTOR (2025-10-19): AuthController에서 리다이렉트 로직 분리 (LOC 제약 준수)
 *
 * 책임:
 * - 사용자 테넌트 수 확인
 * - 테넌트 수에 따른 적절한 리다이렉트 경로 결정
 * - 단일 테넌트 자동 리다이렉트 처리
 */

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AuthRedirectService
{
    /**
     * 로그인 후 사용자 테넌트 수에 따라 적절한 리다이렉트 처리
     *
     * @param  User  $user  인증된 사용자
     * @return RedirectResponse 리다이렉트 응답
     */
    public function redirectAfterLogin(User $user): RedirectResponse
    {
        $tenantCount = $this->countUserTenants($user);

        // 테넌트 0개 → 온보딩 (Filament tenantRegistration 경로)
        if ($tenantCount === 0) {
            /** @var RedirectResponse */
            return redirect('/org/new');
        }

        // 테넌트 1개 → 자동 리다이렉트
        if ($tenantCount === 1) {
            return $this->redirectToSingleTenant($user);
        }

        // 테넌트 2개+ → 계류페이지
        /** @var RedirectResponse */
        return redirect()->route('tenant.selector');
    }

    /**
     * 사용자가 소속된 테넌트 총 개수 확인
     *
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * TenantUser 모델을 통해 실제 테넌트 수 계산
     *
     * @param  User  $user  사용자
     * @return int 고유 테넌트 수 (Organization + Store + Brand)
     */
    private function countUserTenants(User $user): int
    {
        // TenantUser 레코드에서 고유한 테넌트 개수 조회
        return \DB::table('tenant_users')
            ->where('user_id', $user->id)
            ->whereIn('tenant_type', [
                ScopeType::ORGANIZATION->value,
                ScopeType::STORE->value,
                ScopeType::BRAND->value,
            ])
            ->count();
    }

    /**
     * 단일 테넌트 소속 시 자동 리다이렉트 처리
     *
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * @param  User  $user  사용자
     * @return RedirectResponse 테넌트 패널 리다이렉트
     *
     * @throws \LogicException 테넌트를 찾을 수 없는 경우
     */
    private function redirectToSingleTenant(User $user): RedirectResponse
    {
        // Organization 확인
        /** @var object{tenant_id: int}|null $orgTenant */
        $orgTenant = \DB::table('tenant_users')
            ->where('user_id', $user->id)
            ->where('tenant_type', ScopeType::ORGANIZATION->value)
            ->first();

        if ($orgTenant) {
            return redirect("/org/{$orgTenant->tenant_id}");
        }

        // Store 확인
        /** @var object{tenant_id: int}|null $storeTenant */
        $storeTenant = \DB::table('tenant_users')
            ->where('user_id', $user->id)
            ->where('tenant_type', ScopeType::STORE->value)
            ->first();

        if ($storeTenant) {
            return redirect("/store/{$storeTenant->tenant_id}");
        }

        // Brand 확인
        /** @var object{tenant_id: int}|null $brandTenant */
        $brandTenant = \DB::table('tenant_users')
            ->where('user_id', $user->id)
            ->where('tenant_type', ScopeType::BRAND->value)
            ->first();

        if ($brandTenant) {
            return redirect("/brand/{$brandTenant->tenant_id}");
        }

        throw new \LogicException('No tenant found despite count being 1');
    }
}
