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

if (!function_exists('scopeContext')) {
    /**
     * 스코프 컨텍스트 서비스 인스턴스 반환
     * 
     * @return \App\Services\ScopeContextService
     */
    function scopeContext(): \App\Services\ScopeContextService
    {
        return app(\App\Services\ScopeContextService::class);
    }
}

if (!function_exists('currentScopeTeamId')) {
    /**
     * 현재 활성 스코프의 team_id 반환 (Spatie Permission용)
     * 
     * @return int|null
     */
    function currentScopeTeamId(): ?int
    {
        return scopeContext()->getCurrentTeamId();
    }
}
