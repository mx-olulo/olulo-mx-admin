<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * Handle an incoming request.
     *
     * Panel ID와 현재 Tenant(Role)의 scope_type이 일치하는지 검증
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $scopeType): Response
    {
        $tenant = Filament::getTenant();

        // Tenant가 Role이 아니면 통과 (다른 검증 로직에서 처리)
        if (! $tenant instanceof Role) {
            return $next($request);
        }

        // scope_type이 일치하지 않으면 403
        if ($tenant->scope_type !== $scopeType) {
            abort(403, "This panel requires {$scopeType} scope, but your role has {$tenant->scope_type} scope.");
        }

        return $next($request);
    }
}
