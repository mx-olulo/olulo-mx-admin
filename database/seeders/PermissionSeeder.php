<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ScopeType;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Permission Seeder
 *
 * Organization 관련 권한을 생성하고 Role에 할당합니다.
 * PLATFORM/SYSTEM 스코프는 모든 권한을 가지며,
 * ORGANIZATION 스코프는 제한된 권한을 가집니다.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permission 캐시 초기화
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Organization 관련 권한 정의
        $permissions = [
            'view-organizations',      // Organization 목록/상세 조회
            'create-organizations',    // Organization 생성 (PLATFORM/SYSTEM만)
            'update-organizations',    // Organization 수정
            'delete-organizations',    // Organization 삭제 (PLATFORM/SYSTEM만)
            'restore-organizations',   // Organization 복원 (PLATFORM/SYSTEM만)
            'force-delete-organizations', // Organization 영구 삭제 (PLATFORM/SYSTEM만)
            'view-activities',         // Activity Log 조회
        ];

        // 권한 생성 (중복 생성 방지)
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // PLATFORM 역할에 모든 권한 부여
        $this->assignPermissionsToScope(ScopeType::PLATFORM, $permissions);

        // SYSTEM 역할에 모든 권한 부여
        $this->assignPermissionsToScope(ScopeType::SYSTEM, $permissions);

        // ORGANIZATION 역할에 제한된 권한 부여
        $this->assignPermissionsToScope(ScopeType::ORGANIZATION, [
            'view-organizations',
            'update-organizations',
            'view-activities',
        ]);
    }

    /**
     * 특정 스코프의 모든 역할에 권한 할당
     *
     * syncPermissions()를 사용하여 멱등성(Idempotency) 보장
     * 여러 번 실행해도 동일한 결과 유지
     *
     * @param  ScopeType  $scopeType  권한을 부여할 스코프 타입
     * @param  array<int, string>  $permissions  부여할 권한 목록
     */
    protected function assignPermissionsToScope(ScopeType $scopeType, array $permissions): void
    {
        // 해당 스코프의 모든 역할 조회
        $roles = Role::where('scope_type', $scopeType->value)->get();

        foreach ($roles as $role) {
            // team_id 컨텍스트 설정 (Spatie Permission)
            setPermissionsTeamId($role->team_id);

            // syncPermissions: 기존 권한을 제거하고 새 권한으로 대체
            // 멱등성 보장: 중복 실행해도 예외 발생하지 않음
            $role->syncPermissions($permissions);
        }

        // 컨텍스트 초기화
        setPermissionsTeamId(null);
    }
}
