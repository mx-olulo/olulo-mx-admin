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
