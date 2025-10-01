<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 고객 컨텍스트 미들웨어
 *
 * QR 스캔 시 전달되는 store, table, seat 파라미터를 세션에 저장하여
 * 주문 생성 시 컨텍스트로 사용합니다.
 */
class CustomerContextMiddleware
{
    /**
     * QR 스캔 파라미터를 세션에 저장
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // QR 파라미터 처리 (store, table, seat)
        if ($request->has('store') || $request->has('table') || $request->has('seat')) {
            $context = [
                'store' => $request->query('store'),
                'table' => $request->query('table'),
                'seat' => $request->query('seat'),
                'timestamp' => now()->toIso8601String(),
            ];

            // 세션에 컨텍스트 저장
            session()->put('customer_context', $context);
        }

        return $next($request);
    }
}
