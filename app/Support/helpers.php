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
     */
    function currentTenant(): ?\App\Models\Role
    {
        return \Filament\Facades\Filament::getTenant();
    }
}

if (! function_exists('currentTeamId')) {
    /**
     * 현재 테넌트의 team_id 반환 (Spatie Permission용)
     */
    function currentTeamId(): ?int
    {
        $tenant = currentTenant();

        return $tenant?->team_id;
    }
}
