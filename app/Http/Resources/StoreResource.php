<?php

// @CODE:STORE-LIST-001:API | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Store API Resource
 *
 * SPEC-STORE-LIST-001: Store 데이터 변환
 * - organization 필드에 실제 소속 조직 반환 (getOwnerOrganization)
 * - Brand를 통한 간접 소속도 처리
 *
 * @mixin Store
 */
class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            // 실제 소속 조직 반환 (Brand를 통한 간접 소속 포함)
            'organization' => $this->getOwnerOrganization() instanceof \App\Models\Organization ? [
                'id' => $this->getOwnerOrganization()->id,
                'name' => $this->getOwnerOrganization()->name,
                'description' => $this->getOwnerOrganization()->description,
                'contact_email' => $this->getOwnerOrganization()->contact_email,
                'contact_phone' => $this->getOwnerOrganization()->contact_phone,
                'is_active' => $this->getOwnerOrganization()->is_active,
                'created_at' => $this->getOwnerOrganization()->created_at?->toISOString(),
                'updated_at' => $this->getOwnerOrganization()->updated_at?->toISOString(),
            ] : null,
        ];
    }
}
