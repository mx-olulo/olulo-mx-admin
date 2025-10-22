<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static OrganizationFactory factory($count = null, $state = [])
 */
class Organization extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

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
            ->logOnly(['name', 'description', 'contact_email', 'contact_phone', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('organization');
    }
}
