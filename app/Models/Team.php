<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Team (가상 테넌트)
 *
 * Filament Tenancy를 위한 가상 모델입니다.
 * 실제로는 roles 테이블의 team_id를 사용합니다.
 *
 * 이 모델은 데이터베이스 테이블이 없으며,
 * Role의 team_id + scope 정보를 조합하여 동적으로 생성됩니다.
 */
class Team extends Model
{
    /**
     * 테이블 사용 안 함 (가상 모델)
     */
    protected $table = null;

    /**
     * timestamps 사용 안 함
     */
    public $timestamps = false;

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'scope_type',
        'scope_ref_id',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'id' => 'integer',
        'scope_ref_id' => 'integer',
    ];

    /**
     * Role에서 Team 인스턴스 생성
     */
    public static function fromRole(Role $role): self
    {
        $team = new self();
        $team->id = $role->team_id;
        $team->name = self::generateName($role);
        $team->slug = self::generateSlug($role);
        $team->scope_type = $role->scope_type;
        $team->scope_ref_id = $role->scope_ref_id;
        $team->exists = true;

        return $team;
    }

    /**
     * team_id로 Team 인스턴스 찾기
     */
    public static function find($id): ?self
    {
        $role = Role::where('team_id', $id)->first();

        if (! $role) {
            return null;
        }

        return self::fromRole($role);
    }

    /**
     * Team 이름 생성
     */
    private static function generateName(Role $role): string
    {
        // TODO: 실제 엔터티 이름 가져오기 (Organization/Brand/Store)
        return match ($role->scope_type) {
            'ORG' => "Organization #{$role->scope_ref_id}",
            'BRAND' => "Brand #{$role->scope_ref_id}",
            'STORE' => "Store #{$role->scope_ref_id}",
            default => "Team #{$role->team_id}",
        };
    }

    /**
     * Team slug 생성
     */
    private static function generateSlug(Role $role): string
    {
        return strtolower("{$role->scope_type}-{$role->scope_ref_id}");
    }

    /**
     * 저장 방지 (가상 모델)
     */
    public function save(array $options = []): bool
    {
        // 가상 모델이므로 저장하지 않음
        return true;
    }
}
