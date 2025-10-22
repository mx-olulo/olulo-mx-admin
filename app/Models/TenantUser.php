<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Unit/Models/TenantUserTest.php
 *
 * 테넌트 사용자 피벗 모델
 * - Admin과 Tenant(Organization, Brand, Store) 간 M:N 관계
 * - Polymorphic 관계 (tenant_type, tenant_id → tenant)
 * - 역할 정보 (role: owner/manager/viewer)
 *
 * @property int $id
 * @property int $user_id
 * @property string $tenant_type 'ORG'|'BRD'|'STR'
 * @property int $tenant_id
 * @property string $role 'owner'|'manager'|'viewer'
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read Model $tenant
 */
class TenantUser extends Model
{
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tenant_type',
        'tenant_id',
        'role',
    ];

    /**
     * 사용자 관계
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic 관계: Tenant (Organization, Brand, Store)
     *
     * @return MorphTo<Model, $this>
     */
    public function tenant(): MorphTo
    {
        return $this->morphTo('tenant', 'tenant_type', 'tenant_id');
    }

    /**
     * Activity Log 설정
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'tenant_type', 'tenant_id', 'role'])
            ->logOnlyDirty()
            ->useLogName('tenant_user');
    }
}
