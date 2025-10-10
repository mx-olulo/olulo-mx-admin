<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Scope 모델
 *
 * 다형 스코프를 정규화하여 Spatie Permission의 team_id로 사용.
 *
 * 예시:
 * - scope_type='ORG', scope_ref_id=1 → organizations 테이블의 id=1
 * - scope_type='BRAND', scope_ref_id=5 → brands 테이블의 id=5
 * - scope_type='STORE', scope_ref_id=10 → stores 테이블의 id=10
 *
 * @property int $id Spatie의 team_id로 사용됨
 * @property string $scope_type 'ORG'|'BRAND'|'STORE'
 * @property int $scope_ref_id 실제 엔터티의 PK
 */
class Scope extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_ref_id',
    ];

    /**
     * 스코프 타입 상수
     */
    public const TYPE_ORG = 'ORG';

    public const TYPE_BRAND = 'BRAND';

    public const TYPE_STORE = 'STORE';

    /**
     * 다형 관계: 실제 스코프 엔터티
     *
     * TODO: Organization, Brand, Store 모델 생성 후 활성화
     * AppServiceProvider에서 morphMap 등록 필요
     *
     * @return MorphTo
     */
    // public function scopeable(): MorphTo
    // {
    //     return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_ref_id');
    // }

    /**
     * 특정 스코프 조회 또는 생성
     */
    public static function findOrCreateScope(string $scopeType, int $scopeRefId): self
    {
        return static::firstOrCreate([
            'scope_type' => $scopeType,
            'scope_ref_id' => $scopeRefId,
        ]);
    }
}
