<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Team;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Organization) {
            // Ensure or get team for this organization
            $team = Team::firstOrCreate(
                [
                    'tenant_type' => Organization::class,
                    'tenant_id' => $tenant->getKey(),
                    'scope_type' => ScopeType::ORGANIZATION->value,
                ],
                [
                    'name' => $tenant->name ?? ('Organization #' . $tenant->getKey()),
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
