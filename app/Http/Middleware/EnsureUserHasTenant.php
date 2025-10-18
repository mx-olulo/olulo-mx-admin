<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTenant
{
    /**
     * 사용자가 테넌트를 가지고 있는지 확인
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $panel = Filament::getCurrentPanel();

        // Platform/System 패널은 테넌트 불필요
        if ($panel !== null && in_array($panel->getId(), ['platform', 'system'], true)) {
            return $next($request);
        }

        // 온보딩 페이지 제외
        if ($request->routeIs('filament.*.pages.onboarding-wizard')) {
            return $next($request);
        }

        // 테넌트 없으면 온보딩으로 리디렉션
        if ($user instanceof \App\Models\User && $panel !== null && $user->getTenants($panel)->isEmpty()) {
            return redirect()->route('filament.store.pages.onboarding-wizard');
        }

        return $next($request);
    }
}
