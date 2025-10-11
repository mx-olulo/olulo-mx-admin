<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 소속 브랜드
     *
     * @return HasMany<Brand, $this>
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * 직접 소속 매장 (브랜드 없이)
     *
     * @return HasMany<Store, $this>
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * 다형 관계: Organization 스코프를 가진 Role들
     *
     * @return MorphMany<Role, $this>
     */
    public function roles(): MorphMany
    {
        return $this->morphMany(Role::class, 'scopeable', 'scope_type', 'scope_ref_id');
    }

    /**
     * Activity Log 설정
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'contact_email', 'contact_phone', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('organization');
    }
}
