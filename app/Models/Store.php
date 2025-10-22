<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RelationshipType;
use Database\Factories\StoreFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * @method static StoreFactory factory($count = null, $state = [])
 */
class Store extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'brand_id',
        'organization_id',
        'name',
        'description',
        'address',
        'phone',
        'relationship_type',
        'is_active',
    ];

    protected $casts = [
        'relationship_type' => RelationshipType::class,
        'is_active' => 'boolean',
    ];

    /**
     * 소속 브랜드 (nullable)
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * 소속 조직 (nullable, 브랜드 없이 직접 소속)
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * 매장의 실제 소속 조직 반환
     * Brand를 통해 소속되었거나, 직접 소속되었거나, 독립적인 경우
     */
    public function getOwnerOrganization(): ?Organization
    {
        if ($this->brand_id && $this->brand) {
            return $this->brand->organization;
        }

        return $this->organization;
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
            ->logOnly(['brand_id', 'organization_id', 'name', 'description', 'address', 'phone', 'relationship_type', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('store');
    }
}
