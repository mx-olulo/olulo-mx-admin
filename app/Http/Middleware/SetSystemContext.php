<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\System;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSystemContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // System은 유일 인스턴스 (ID=1)
        $system = System::first();

        if ($system) {
            // System의 고정 team_id 설정 (Platform과 구분하기 위해 offset 사용)
            setPermissionsTeamId($system->id + 1000);

            // Reset user cached relations to reflect team context
            if ($user = $request->user()) {
                $user->unsetRelation('roles')->unsetRelation('permissions');
            }
        }

        return $next($request);
    }
}
