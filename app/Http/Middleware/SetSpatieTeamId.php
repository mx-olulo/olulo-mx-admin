<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetSpatieTeamId Middleware
 *
 * Filament Tenancy와 Spatie Permission을 통합합니다.
 * Filament가 관리하는 현재 테넌트의 ID를 Spatie Permission의 team_id로 설정합니다.
 */
class SetSpatieTeamId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Filament가 관리하는 현재 테넌트 가져오기
        $tenant = Filament::getTenant();

        if ($tenant) {
            // Spatie Permission에 team_id 설정
            setPermissionsTeamId($tenant->id);
        }

        return $next($request);
    }
}
