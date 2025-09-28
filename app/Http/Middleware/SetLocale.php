<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * 세션에 저장된 로케일을 애플리케이션에 적용하는 미들웨어
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 세션에 저장된 locale 또는 기본 locale 사용
        $locale = session('locale', config('app.locale', 'ko'));

        // 애플리케이션 로케일 설정
        App::setLocale($locale);

        return $next($request);
    }
}