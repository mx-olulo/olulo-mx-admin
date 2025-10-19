<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RelationshipType;
use Database\Factories\BrandFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * @method static BrandFactory factory($count = null, $state = [])
 */
class Brand extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'relationship_type',
        'is_active',
    ];

    protected $casts = [
        'relationship_type' => RelationshipType::class,
        'is_active' => 'boolean',
    ];

    /**
     * 모델 부트 메서드
     *
     * Brand 삭제 시 소속 Store들을 상위 Organization으로 이관
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Brand $brand): void {
            // 소속 Store들의 brand_id를 NULL로 설정하고
            // organization_id를 Brand의 상위 조직으로 이관
            $brand->stores()->update([
                'brand_id' => null,
                'organization_id' => $brand->organization_id,
            ]);
        });
    }

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
            ->logOnly(['organization_id', 'name', 'description', 'relationship_type', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('brand');
    }
}
