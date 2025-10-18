<?php

declare(strict_types=1);

namespace App\Permissions;

use App\Enums\ScopeType;
use App\Models\Role;
use Filament\Facades\Filament;
use Spatie\Permission\DefaultTeamResolver;

class TenantTeamResolver extends DefaultTeamResolver
{
    /**
     * 요청당 캐싱을 위한 정적 변수
     * 동일 테넌트에 대한 중복 쿼리 방지
     */
    protected static ?int $cachedTeamId = null;

    protected static ?string $cachedTenantKey = null;

    /**
     * 캐시 초기화 (테스트용)
     */
    public static function clearCache(): void
    {
        static::$cachedTeamId = null;
        static::$cachedTenantKey = null;
    }

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
            // 캐시 키: 테넌트 클래스 + ID 조합
            $tenantKey = $tenant::class . ':' . $tenant->getKey();

            // 동일 테넌트면 캐시된 team_id 반환 (쿼리 스킵)
            if (static::$cachedTenantKey === $tenantKey) {
                return static::$cachedTeamId;
            }

            // morphMap에서 scope_type 조회 (whereHasMorph 대신 직접 조건 비교)
            $scopeType = array_search($tenant::class, ScopeType::getMorphMap(), true);

            if ($scopeType === false) {
                // 매핑되지 않은 테넌트 타입은 팀 컨텍스트 없음
                return null;
            }

            /** @var int|null $teamId */
            $teamId = Role::query()
                ->where('scope_type', $scopeType)
                ->where('scope_ref_id', $tenant->getKey())
                ->value('team_id');

            // 캐시 저장
            static::$cachedTenantKey = $tenantKey;
            static::$cachedTeamId = $teamId !== null ? (int) $teamId : null;

            return static::$cachedTeamId;
        }

        // 그 외에는 팀 컨텍스트 없음
        return null;
    }
}
