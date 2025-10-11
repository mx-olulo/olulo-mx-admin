<?php

/**
 * 글로벌 헬퍼 함수
 */
if (! function_exists('csp_nonce')) {
    /**
     * CSP Nonce 값을 가져옵니다.
     *
     * Blade 템플릿에서 <script nonce="{{ csp_nonce() }}">로 사용
     */
    function csp_nonce(): string
    {
        $request = request();

        return $request->attributes->get('csp-nonce', '') ?? '';
    }
}

if (! function_exists('currentTenant')) {
    /**
     * 현재 Filament 테넌트(Role) 반환
     *
     * 목적:
     * - 호출부에서 Filament 파사드에 직접 의존하지 않도록 캡슐화합니다.
     * - 컨트롤러/위젯/뷰에서 현재 테넌트(= Role 인스턴스)에 일관된 방식으로 접근합니다.
     *
     * 사용 예:
     * - Blade/Livewire/Filament 위젯에서 현재 컨텍스트 표시: `currentTenant()?->getTenantName()`
     * - 리소스 쿼리 스코프: `Product::where('store_id', currentTenant()?->scope_ref_id)`
     *
     * 주의:
     * - 이 헬퍼는 편의용 래퍼입니다. 필요 없다면 `Filament::getTenant()`를 직접 사용해도 됩니다.
     *
     * TODO (merge 전 점검):
     * - 본 함수가 실제 코드에서 사용되지 않는다면 과감히 제거합니다.
     */
    function currentTenant(): ?\App\Models\Role
    {
        return \Filament\Facades\Filament::getTenant();
    }
}

if (! function_exists('currentTeamId')) {
    /**
     * 현재 테넌트의 team_id 반환 (Spatie Permission 컨텍스트용)
     *
     * 목적:
     * - 호출부에서 테넌트 → team_id 추출 로직을 반복하지 않도록 단일 진입점을 제공합니다.
     * - Spatie `setPermissionsTeamId()`/`getPermissionsTeamId()`와의 결합을 최소화합니다.
     *
     * 사용 예:
     * - 권한 체크 전 컨텍스트 확인: `if (currentTeamId() === null) abort(403);`
     * - 로깅/감사 이벤트에 team_id 포함: `Log::info('ctx', ['team_id' => currentTeamId()]);`
     *
     * 대안:
     * - 현재 요청 컨텍스트에서 team_id는 Spatie의 `getPermissionsTeamId()`로도 얻을 수 있습니다.
     *   프로젝트 정책상 그 함수를 직접 사용하기로 했다면 본 헬퍼는 제거 가능합니다.
     *
     * TODO (merge 전 점검):
     * - 본 함수가 실제 코드에서 사용되지 않는다면 과감히 제거합니다.
     */
    function currentTeamId(): ?int
    {
        $tenant = currentTenant();

        return $tenant?->team_id;
    }
}
