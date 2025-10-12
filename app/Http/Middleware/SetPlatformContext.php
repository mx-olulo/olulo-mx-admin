<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPlatformContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Platform은 유일 인스턴스 (ID=1)
        $platform = Platform::first();

        if ($platform) {
            // Platform의 고정 team_id 설정
            setPermissionsTeamId($platform->id);

            // Reset user cached relations to reflect team context
            if ($user = $request->user()) {
                $user->unsetRelation('roles')->unsetRelation('permissions');
            }
        }

        return $next($request);
    }
}
