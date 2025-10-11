<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * 스코프 타입 상수
     */
    public const TYPE_ORG = 'ORG';

    public const TYPE_BRAND = 'BRAND';

    public const TYPE_STORE = 'STORE';

    /**
     * 유효한 스코프 타입 목록
     */
    public const VALID_TYPES = [
        self::TYPE_ORG,
        self::TYPE_BRAND,
        self::TYPE_STORE,
    ];

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
     * 다형 관계: 실제 스코프 엔터티 (Organization/Brand/Store)
     *
     * TODO: Organization, Brand, Store 모델 생성 후 활성화
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
}
