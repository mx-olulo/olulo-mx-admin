<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ScopeType;
use App\Models\Brand;
use App\Models\Team;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetBrandContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Brand) {
            // Ensure or get team for this brand
            $team = Team::firstOrCreate(
                [
                    'tenant_type' => Brand::class,
                    'tenant_id' => $tenant->getKey(),
                    'scope_type' => ScopeType::BRAND->value,
                ],
                [
                    'name' => $tenant->name ?? ('Brand #' . $tenant->getKey()),
                ]
            );

            // Set Spatie Permission team context
            setPermissionsTeamId($team->getKey());

            // Reset user cached relations to reflect new team context
            if ($user = $request->user()) {
                $user->unsetRelation('roles')->unsetRelation('permissions');
            }
        }

        return $next($request);
    }
}
