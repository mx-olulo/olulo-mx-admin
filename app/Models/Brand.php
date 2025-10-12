<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Brand extends Model implements HasCurrentTenantLabel
{
    use LogsActivity;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 소속 조직
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * 소속 매장
     *
     * @return HasMany<Store, $this>
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * 다형 관계: Brand 스코프를 가진 Role들
     *
     * @return MorphMany<Role, $this>
     */
    public function roles(): MorphMany
    {
        return $this->morphMany(Role::class, 'scopeable', 'scope_type', 'scope_ref_id');
    }

    /**
     * Filament Tenancy: 현재 테넌트 라벨
     */
    public function getCurrentTenantLabel(): string
    {
        return $this->name;
    }

    /**
     * Activity Log 설정
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['organization_id', 'name', 'description', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('brand');
    }
}
