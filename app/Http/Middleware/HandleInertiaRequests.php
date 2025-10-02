<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia 요청 처리 미들웨어
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * 루트 템플릿
     */
    protected $rootView = 'customer.app';

    /**
     * 버전 결정 (에셋 캐싱)
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * 모든 Inertia 응답에 공유할 데이터
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
