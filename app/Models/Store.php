<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Store extends Model
{
    protected $fillable = [
        'brand_id',
        'organization_id',
        'name',
        'description',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 소속 브랜드 (nullable)
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * 소속 조직 (nullable, 브랜드 없이 직접 소속)
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * 다형 관계: Store 스코프를 가진 Role들
     */
    public function roles(): MorphMany
    {
        return $this->morphMany(Role::class, 'scopeable', 'scope_type', 'scope_ref_id');
    }

    /**
     * 매장의 실제 소속 조직 반환
     * Brand를 통해 소속되었거나, 직접 소속되었거나, 독립적인 경우
     */
    public function getOwnerOrganization(): ?Organization
    {
        if ($this->brand_id && $this->brand) {
            /** @var Organization|null */
            return $this->brand->organization;
        }

        /** @var Organization|null */
        return $this->organization;
    }
}
