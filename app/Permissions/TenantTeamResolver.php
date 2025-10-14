<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Models\Role;
use Filament\Facades\Filament;
use Spatie\Permission\DefaultTeamResolver;

class TenantTeamResolver extends DefaultTeamResolver
{
    public function getPermissionsTeamId(): int|string|null
    {
        // 우선 수동 설정된 team id가 있으면 그대로 사용
        $explicit = parent::getPermissionsTeamId();
        if ($explicit !== null) {
            return $explicit;
        }

        // Filament 테넌트가 있으면 해당 테넌트에 매핑된 Role의 team_id를 사용
        $tenant = Filament::getTenant();
        if ($tenant) {
            // once()로 요청 단위 메모이제이션 (중복 쿼리 방지)
            return once(function () use ($tenant): ?int {
                $teamId = Role::query()
                    ->whereHasMorph('scopeable', $tenant::class, function ($query) use ($tenant): void {
                        $query->whereKey($tenant->getKey());
                    })
                    ->value('team_id');

                return $teamId !== null ? (int) $teamId : null;
            });
        }

        // 그 외에는 팀 컨텍스트 없음
        return null;
    }
}
