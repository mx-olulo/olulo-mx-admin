<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'global_role',
        'firebase_uid',
        'provider',
        'firebase_claims',
        'phone_number',
        'firebase_phone',
        'avatar_url',
        'email_verified_at',
        'locale',
        'last_login_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'firebase_claims',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'firebase_claims' => 'array',
            'two_factor_recovery_codes' => 'array',
            'user_type' => \App\Enums\UserType::class,
        ];
    }

    /**
     * Firebase UID로 사용자 찾기
     */
    public static function findByFirebaseUid(string $firebaseUid): ?self
    {
        return static::where('firebase_uid', $firebaseUid)->first();
    }

    /**
     * Firebase 사용자인지 확인
     */
    public function isFirebaseUser(): bool
    {
        return ! empty($this->firebase_uid);
    }

    /**
     * Firebase 커스텀 클레임 가져오기
     *
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function getFirebaseClaim(?string $key = null): mixed
    {
        // firebase_claims는 casts()에서 'array'로 선언되어 있지만,
        // 아직 설정되지 않은 경우 null일 수 있으므로 안전하게 처리
        $claims = $this->firebase_claims;
        if (! is_array($claims)) {
            $claims = [];
        }

        if ($key === null) {
            return $claims;
        }

        return $claims[$key] ?? null;
    }

    /**
     * Filament 패널 접근 권한 확인
     *
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * US2: User 글로벌 접근 제한
     * - User: Platform/System 패널만 접근 (global_role 기반)
     * - Admin: Organization/Brand/Store 패널만 접근 (tenant_users 기반)
     * - Customer: 모든 패널 접근 불가
     *
     * @param  Panel  $panel  Filament 패널 인스턴스
     * @return bool 접근 가능 여부
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Customer는 모든 패널 차단
        if ($this->user_type === \App\Enums\UserType::CUSTOMER) {
            return false;
        }

        $scopeType = \App\Enums\ScopeType::fromPanelId($panel->getId());

        // Platform/System 패널: User 타입만 접근 (global_role 기반)
        if ($scopeType === \App\Enums\ScopeType::PLATFORM) {
            return $this->user_type === \App\Enums\UserType::USER
                && $this->hasGlobalRole('platform_admin');
        }

        if ($scopeType === \App\Enums\ScopeType::SYSTEM) {
            return $this->user_type === \App\Enums\UserType::USER
                && $this->hasGlobalRole('system_admin');
        }

        // Organization/Brand/Store 패널: Admin 타입만 접근 (tenant_users 기반)
        if (in_array($scopeType, [
            \App\Enums\ScopeType::ORGANIZATION,
            \App\Enums\ScopeType::BRAND,
            \App\Enums\ScopeType::STORE,
        ], true)) {
            // Admin 타입만 테넌트 패널 접근 가능
            if ($this->user_type !== \App\Enums\UserType::ADMIN) {
                return false;
            }

            return $this->canAccessTenantPanel($panel->getId());
        }

        // 기타 패널: 기본 거부
        return false;
    }

    /**
     * 테넌트 패널 접근 권한 확인 (헬퍼 메서드)
     *
     * @CODE:TENANCY-AUTHZ-001 | SPEC: SPEC-TENANCY-AUTHZ-001.md
     *
     * 온보딩 위자드 경로 예외 처리:
     * - org/new → Organization 온보딩 (Filament tenantRegistration)
     * - store/new → Store 온보딩 (Filament tenantRegistration)
     * - brand: 온보딩 없음 (멤버십 검증 필수)
     *
     * @param  string  $panelId  패널 ID (org, brand, store)
     * @return bool 접근 가능 여부
     */
    private function canAccessTenantPanel(string $panelId): bool
    {
        // 온보딩 위자드 예외 처리 (org, store만 해당)
        // 1. 직접 접근: /org/new, /store/new
        // 2. Livewire 요청: Referer 헤더로 판단 (폼 제출)
        if (in_array($panelId, ['org', 'store'])) {
            if (request()->is("{$panelId}/new")) {
                return true;
            }

            // Livewire AJAX 요청 예외 처리
            $referer = request()->header('Referer');
            if ($referer && str_contains($referer, "/{$panelId}/new")) {
                return true;
            }
        }

        // 테넌트 컨텍스트 확인
        $tenant = \Filament\Facades\Filament::getTenant();

        // Filament이 아직 테넌트를 설정하지 않은 경우 (초기 접근)
        // URL에서 테넌트 ID를 추출하여 직접 확인
        if (! $tenant instanceof Model) {
            // URL 패턴: org/1, store/2 등 (request()->path()는 앞의 /가 없음)
            if (preg_match("#^{$panelId}/(\d+)#", request()->path(), $matches)) {
                $tenantId = (int) $matches[1];
                $tenant = $this->getTenantModelByPanelId($panelId, $tenantId);

                if ($tenant instanceof Model) {
                    return $this->canAccessTenant($tenant);
                }
            }

            return false;
        }

        // 멤버십 검증
        return $this->canAccessTenant($tenant);
    }

    /**
     * 패널 ID와 테넌트 ID로 테넌트 모델 조회
     */
    private function getTenantModelByPanelId(string $panelId, int $tenantId): ?Model
    {
        $scopeType = match ($panelId) {
            'org' => \App\Enums\ScopeType::ORGANIZATION,
            'store' => \App\Enums\ScopeType::STORE,
            'brand' => \App\Enums\ScopeType::BRAND,
            default => null,
        };

        if ($scopeType === null) {
            return null;
        }

        $modelClass = $scopeType->getModelClass();

        return $modelClass::find($tenantId);
    }

    /**
     * 사용자 로케일 가져오기 (없으면 기본값)
     */
    public function getLocaleAttribute(): string
    {
        /** @var string $defaultLocale */
        $defaultLocale = config('app.locale', 'es-MX');

        /** @var string|null $locale */
        $locale = $this->attributes['locale'] ?? null;

        return $locale ?? $defaultLocale;
    }

    /**
     * 마지막 로그인 시간 업데이트
     */
    public function updateLastLoginAt(): void
    {
        $this->update([
            'last_login_at' => now(),
        ]);
    }

    /**
     * 전화번호로 사용자 찾기
     */
    public static function findByPhoneNumber(string $phoneNumber): ?self
    {
        return static::where('phone_number', $phoneNumber)
            ->orWhere('firebase_phone', $phoneNumber)
            ->first();
    }

    /**
     * 이메일 또는 전화번호로 사용자 찾기
     */
    public static function findByEmailOrPhone(string $identifier): ?self
    {
        return static::where('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->orWhere('firebase_phone', $identifier)
            ->first();
    }

    /**
     * Firebase 사용자 데이터로 업데이트
     *
     * @param  array<string, mixed>  $firebaseUserData  Firebase에서 가져온 사용자 데이터
     */
    public function updateFromFirebase(array $firebaseUserData): void
    {
        $updateData = [];

        if (isset($firebaseUserData['name']) && $firebaseUserData['name'] !== $this->name) {
            $updateData['name'] = $firebaseUserData['name'];
        }

        if (isset($firebaseUserData['email']) && $firebaseUserData['email'] !== $this->email) {
            $updateData['email'] = $firebaseUserData['email'];
        }

        if (isset($firebaseUserData['phone_number']) && $firebaseUserData['phone_number'] !== $this->firebase_phone) {
            $updateData['firebase_phone'] = $firebaseUserData['phone_number'];
        }

        if (isset($firebaseUserData['picture']) && $firebaseUserData['picture'] !== $this->avatar_url) {
            $updateData['avatar_url'] = $firebaseUserData['picture'];
        }

        if (isset($firebaseUserData['email_verified']) && $firebaseUserData['email_verified'] && ! $this->email_verified_at) {
            $updateData['email_verified_at'] = now();
        }

        if (! empty($updateData)) {
            $this->update($updateData);
        }
    }

    /**
     * Filament Tenancy: 사용자가 접근 가능한 테넌트 목록
     *
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * TenantUser 모델 기반 테넌트 목록 조회 (Spatie Role 제거)
     * tenant_users 테이블에서 직접 조회 및 Morph 관계 eager loading
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function getTenants(Panel $panel): Collection
    {
        $scopeType = \App\Enums\ScopeType::fromPanelId($panel->getId());

        if (! $scopeType instanceof \App\Enums\ScopeType) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        // tenant_users에서 특정 타입의 테넌트 조회
        $tenants = $this->tenantUsers()
            ->where('tenant_type', $scopeType->value)
            ->with('tenant') // Morph 관계 eager loading
            ->get()
            ->pluck('tenant')
            ->filter() // null 제거
            ->unique(fn ($tenant): string => $tenant::class . ':' . $tenant->getKey())
            ->values();

        /** @var \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model> */
        return $tenants->toBase();
    }

    /**
     * Filament Tenancy: 사용자가 특정 테넌트에 접근 가능한지 확인
     *
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * TenantUser 모델 기반 테넌트 접근 권한 확인 (Spatie Role 제거)
     * tenant_users 테이블에서 직접 조회
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // morphMap에서 tenant_type 조회
        $tenantType = array_search($tenant::class, \App\Enums\ScopeType::getMorphMap(), true);

        if ($tenantType === false) {
            // 매핑되지 않은 테넌트 타입은 접근 불가
            return false;
        }

        // tenant_users 테이블에서 직접 조회
        return $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->where('tenant_id', $tenant->getKey())
            ->exists();
    }

    /**
     * Activity Log 설정
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone_number', 'locale', 'email_verified_at'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['last_login_at', 'remember_token'])
            ->useLogName('user');
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Feature/Tenancy/UserTenantRelationTest.php
     *
     * TenantUser 관계 (HasMany)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<TenantUser>
     */
    public function tenantUsers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 특정 타입의 테넌트 목록 조회
     *
     * @param  string  $tenantType  'ORG', 'BRD', 'STR'
     * @return \Illuminate\Support\Collection<int, Model>
     */
    public function getTenantsByType(string $tenantType): \Illuminate\Support\Collection
    {
        return $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->with('tenant')
            ->get()
            ->pluck('tenant')
            ->filter() // null 제거
            ->values(); // 키 리인덱싱
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 특정 테넌트에서의 역할 조회
     *
     * @param  Model  $model  Organization, Brand, Store
     * @return string|null 'owner', 'manager', 'viewer' 또는 null
     */
    public function getRoleForTenant(Model $model): ?string
    {
        $tenantType = array_search($model::class, \App\Enums\ScopeType::getMorphMap(), true);

        if ($tenantType === false) {
            return null;
        }

        $tenantUser = $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->where('tenant_id', $model->getKey())
            ->first();

        return $tenantUser?->role;
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 특정 테넌트에서 특정 역할 보유 여부 확인
     *
     * @param  Model  $model  Organization, Brand, Store
     * @param  string  $role  'owner', 'manager', 'viewer'
     */
    public function hasRoleForTenant(Model $model, string $role): bool
    {
        return $this->getRoleForTenant($model) === $role;
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 테넌트 관리 권한 확인 (owner 또는 manager)
     *
     * @param  Model  $model  Organization, Brand, Store
     */
    public function canManageTenant(Model $model): bool
    {
        $role = $this->getRoleForTenant($model);

        return in_array($role, ['owner', 'manager'], true);
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 테넌트 조회 권한 확인 (모든 역할)
     *
     * @param  Model  $model  Organization, Brand, Store
     */
    public function canViewTenant(Model $model): bool
    {
        return $this->getRoleForTenant($model) !== null;
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 글로벌 역할 확인 (User 타입만)
     *
     * @param  string  $role  'platform_admin', 'system_admin'
     */
    public function hasGlobalRole(string $role): bool
    {
        // User 타입만 글로벌 역할을 가질 수 있음
        if ($this->user_type !== \App\Enums\UserType::USER) {
            return false;
        }

        return $this->global_role === $role;
    }
}
