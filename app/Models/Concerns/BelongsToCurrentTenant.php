<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Filament\Facades\Filament;

/**
 * 현재 Filament 테넌트에 자동으로 소속되도록 하는 Trait
 *
 * 사용법:
 * class Product extends Model
 * {
 *     use BelongsToCurrentTenant;
 * }
 *
 * 자동으로 설정되는 필드:
 * - Organization 패널: organization_id
 * - Brand 패널: brand_id, organization_id (상위 조직도 설정)
 * - Store 패널: store_id, brand_id, organization_id (모든 상위 설정)
 */
trait BelongsToCurrentTenant
{
    protected static function bootBelongsToCurrentTenant(): void
    {
        static::creating(function ($model) {
            $tenant = Filament::getTenant();

            if (! $tenant) {
                return;
            }

            // Organization 패널
            if ($tenant instanceof \App\Models\Organization) {
                $model->organization_id = $tenant->getKey();
            }

            // Brand 패널
            if ($tenant instanceof \App\Models\Brand) {
                $model->brand_id = $tenant->getKey();
                
                // 상위 Organization도 설정
                if ($tenant->organization_id && property_exists($model, 'organization_id')) {
                    $model->organization_id = $tenant->organization_id;
                }
            }

            // Store 패널
            if ($tenant instanceof \App\Models\Store) {
                $model->store_id = $tenant->getKey();
                
                // 상위 Brand 설정
                if ($tenant->brand_id && property_exists($model, 'brand_id')) {
                    $model->brand_id = $tenant->brand_id;
                }
                
                // 상위 Organization 설정
                if ($tenant->organization_id && property_exists($model, 'organization_id')) {
                    $model->organization_id = $tenant->organization_id;
                } elseif ($tenant->brand && $tenant->brand->organization_id && property_exists($model, 'organization_id')) {
                    $model->organization_id = $tenant->brand->organization_id;
                }
            }
        });
    }
}
