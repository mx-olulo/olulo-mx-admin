<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int|null $team_id
 * @property string|null $scope_type
 * @property int|null $scope_ref_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $scopeable
 */
class Role extends SpatieRole
{
    use LogsActivity;

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
     * Platform/System은 단일 인스턴스로 scope_ref_id=1 사용
     *
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
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
     * Activity Log 설정
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'guard_name', 'team_id', 'scope_type', 'scope_ref_id'])
            ->logOnlyDirty()
            ->useLogName('role');
    }
}
