<?php

declare(strict_types=1);

namespace App\Permissions;

use Filament\Facades\Filament;
use Spatie\Permission\DefaultTeamResolver;

class TenantTeamResolver extends DefaultTeamResolver
{
    public function resolveId(): ?int
    {
        // 글로벌 패널(플랫폼/시스템) 또는 비패널 요청: 팀 컨텍스트 없음
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return null;
        }

        // Organization/Brand/Store 등 테넌트는 team_id = 해당 테넌트 PK
        return (int) $tenant->getKey();
    }
}
