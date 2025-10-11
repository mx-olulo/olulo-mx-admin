<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int|null $team_id
 * @property string|null $scope_type
 * @property int|null $scope_ref_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $scopeable
 */
class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'guard_name',
        'team_id',
        'scope_type',
        'scope_ref_id',
    ];

    /**
     * 다형 관계: 실제 스코프 엔터티 (Platform/System/Organization/Brand/Store)
     *
     * TODO: Organization, Brand, Store 모델 생성 후 활성화
     * Platform/System은 단일 인스턴스로 scope_ref_id=1 사용
     */
    public function scopeable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_ref_id');
    }

    /**
     * 특정 스코프의 역할 조회
     */
    public static function findByScope(string $scopeType, int $scopeRefId, string $name): ?self
    {
        return static::where('scope_type', $scopeType)
            ->where('scope_ref_id', $scopeRefId)
            ->where('name', $name)
            ->first();
    }

    /**
     * 글로벌 역할 여부 확인
     */
    public function isGlobal(): bool
    {
        return $this->team_id === null;
    }

    /**
     * 스코프 역할 여부 확인
     */
    public function isScoped(): bool
    {
        return $this->scope_type !== null && $this->scope_ref_id !== null;
    }

    /**
     * Filament Tenancy: 테넌트 이름 (UI 표시용)
     */
    public function getTenantName(): string
    {
        // scopeable 관계를 통해 실제 엔터티 이름 가져오기
        if ($this->scopeable && isset($this->scopeable->name)) {
            return $this->scopeable->name;
        }

        // fallback: ScopeType enum 사용
        $scopeType = $this->scope_type ? \App\Enums\ScopeType::tryFrom($this->scope_type) : null;

        if ($scopeType) {
            return match ($scopeType) {
                \App\Enums\ScopeType::PLATFORM => 'Platform Admin',
                \App\Enums\ScopeType::SYSTEM => 'System Admin',
                \App\Enums\ScopeType::ORGANIZATION,
                \App\Enums\ScopeType::BRAND,
                \App\Enums\ScopeType::STORE => ucfirst(strtolower($scopeType->value))." #{$this->scope_ref_id}",
            };
        }

        return "Team #{$this->team_id}";
    }

    /**
     * Filament Tenancy: 테넌트 slug
     */
    public function getSlug(): string
    {
        return strtolower("{$this->scope_type}-{$this->scope_ref_id}");
    }
}
