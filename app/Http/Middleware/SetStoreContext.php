<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ScopeType;
use App\Models\Store;
use App\Models\Team;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetStoreContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Store) {
            // Ensure or get team for this store
            $team = Team::firstOrCreate(
                [
                    'tenant_type' => Store::class,
                    'tenant_id' => $tenant->getKey(),
                    'scope_type' => ScopeType::STORE->value,
                ],
                [
                    'name' => $tenant->name ?? ('Store #' . $tenant->getKey()),
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
