<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ScopeType;
use App\Services\TenantSelectorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @CODE:AUTH-REDIRECT-001:API | SPEC: SPEC-AUTH-REDIRECT-001.md | TEST: tests/Feature/Auth/RedirectTest.php
 *
 * 테넌트 계류페이지 컨트롤러
 *
 * 여러 테넌트에 소속된 사용자가 로그인 후 접근할 테넌트를 선택하는
 * 계류페이지를 제공합니다.
 *
 * 주요 기능:
 * - 사용자의 Organization/Store/Brand 목록 표시
 * - 테넌트 선택 시 권한 검증
 * - Brand 생성 버튼 없음 (Organization 패널에서만 생성 가능)
 */
class TenantSelectorController extends Controller
{
    /**
     * 테넌트 선택 서비스 인스턴스
     */
    public function __construct(
        private readonly TenantSelectorService $tenantSelectorService
    ) {}

    /**
     * 계류페이지 표시
     *
     * 인증된 사용자의 모든 테넌트 멤버십을 조회하여
     * Organization/Store/Brand 탭으로 구분하여 표시합니다.
     *
     * @return View 계류페이지 뷰
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $tenants = $this->tenantSelectorService->getUserTenants($user);

        return view('auth.tenant-selector', $tenants);
    }

    /**
     * 테넌트 선택 처리
     *
     * 사용자가 선택한 테넌트에 대한 권한을 검증하고,
     * 검증 통과 시 해당 테넌트 패널로 리다이렉트합니다.
     *
     * @param  Request  $request  HTTP 요청
     * @return RedirectResponse 테넌트 패널 리다이렉트
     */
    public function selectTenant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_type' => 'required|in:organization,store,brand',
            'tenant_id' => 'required|integer',
        ]);

        // 테넌트 타입을 ScopeType enum으로 변환
        $scopeType = match ($validated['tenant_type']) {
            'organization' => ScopeType::ORGANIZATION,
            'store' => ScopeType::STORE,
            'brand' => ScopeType::BRAND,
            default => throw new \InvalidArgumentException('Invalid tenant type: ' . $validated['tenant_type']),
        };

        // 권한 검증
        $this->authorizeTenantAccess($scopeType, $validated['tenant_id']);

        // 테넌트 타입별 패널 경로 매핑
        $panelPath = match ($validated['tenant_type']) {
            'organization' => '/org/' . $validated['tenant_id'],
            'store' => '/store/' . $validated['tenant_id'],
            'brand' => '/brand/' . $validated['tenant_id'],
            default => throw new \InvalidArgumentException('Invalid tenant type: ' . $validated['tenant_type']),
        };

        return redirect($panelPath);
    }

    /**
     * 테넌트 접근 권한 검증
     *
     * 사용자가 선택한 테넌트에 대한 멤버십 권한을 확인합니다.
     * 권한이 없으면 403 에러를 발생시킵니다.
     *
     * @param  ScopeType  $scopeType  테넌트 타입
     * @param  int  $id  테넌트 ID
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 에러
     */
    private function authorizeTenantAccess(ScopeType $scopeType, int $id): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $hasAccess = $this->tenantSelectorService->canAccessTenant($user, $scopeType, $id);

        abort_if(! $hasAccess, 403, 'You do not have access to this tenant.');
    }
}
