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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * Platform/System: 글로벌 패널 - 역할 기반 접근 제어
     * Organization/Brand/Store: 테넌트 패널 - 멤버십 기반 접근 제어
     *
     * @param  Panel  $panel  Filament 패널 인스턴스
     * @return bool 접근 가능 여부
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $scopeType = \App\Enums\ScopeType::fromPanelId($panel->getId());

        // Platform/System 패널: 글로벌 역할 확인
        if ($scopeType === \App\Enums\ScopeType::PLATFORM) {
            return $this->hasRole('platform_admin');
        }

        if ($scopeType === \App\Enums\ScopeType::SYSTEM) {
            return $this->hasRole('system_admin');
        }

        // Organization/Brand/Store 패널: 멤버십 확인
        return $this->getTenants($panel)->isNotEmpty();
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
     * Role의 scopeable MorphTo 관계를 통해 실제 테넌트 모델 반환
     * morphMap 설정으로 'ORG' -> Organization::class 자동 매핑
     *
     * Spatie Permission의 team_id 필터를 우회하기 위해 직접 DB 조회
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function getTenants(Panel $panel): Collection
    {
        $scopeType = \App\Enums\ScopeType::fromPanelId($panel->getId());

        if (! $scopeType instanceof \App\Enums\ScopeType) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        // Spatie Permission의 team_id 필터를 우회하여 직접 조회
        // model_has_roles → roles 조인으로 사용자의 모든 역할 조회
        $roleIds = \DB::table('model_has_roles')
            ->where('model_id', $this->getKey())
            ->where('model_type', static::class)
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        // Role 모델에서 scope_type 필터링 및 scopeable eager loading
        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->where('scope_type', $scopeType->value)
            ->with('scopeable')
            ->get();

        // scopeable 추출 및 중복 제거 (모델 클래스 + ID 조합으로 unique)
        $tenants = $roles
            ->pluck('scopeable')
            ->filter() // null 제거
            ->unique(fn ($tenant): string => $tenant::class . ':' . $tenant->getKey())
            ->values();

        /** @var \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model> */
        return $tenants->toBase();
    }

    /**
     * Filament Tenancy: 사용자가 특정 테넌트에 접근 가능한지 확인
     *
     * morphMap 기반 직접 조건 비교로 성능 최적화
     * whereHasMorph 대신 scope_type + scope_ref_id 직접 검색
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // morphMap에서 scope_type 조회
        $scopeType = array_search($tenant::class, \App\Enums\ScopeType::getMorphMap(), true);

        if ($scopeType === false) {
            // 매핑되지 않은 테넌트 타입은 접근 불가
            return false;
        }

        return $this->roles()
            ->where('scope_type', $scopeType)
            ->where('scope_ref_id', $tenant->getKey())
            ->exists();
    }

    /**
     * 사용자가 글로벌 스코프(PLATFORM/SYSTEM) 역할을 보유하는지 확인
     *
     * whereHasMorph로 Platform/System scopeable 확인
     * Eloquent relation 캐싱 활용으로 중복 쿼리 방지
     *
     * @return bool PLATFORM 또는 SYSTEM 스코프 역할 보유 여부
     */
    public function hasGlobalScopeRole(): bool
    {
        // roles relation이 이미 로드되었으면 메모리에서 확인 (쿼리 없음)
        if ($this->relationLoaded('roles')) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Role> $roles */
            $roles = $this->roles;

            return $roles->contains(fn (Role $role): bool => in_array($role->scope_type, [
                \App\Enums\ScopeType::PLATFORM->value,
                \App\Enums\ScopeType::SYSTEM->value,
            ], true));
        }

        // roles가 로드되지 않았으면 쿼리 실행 (모델 클래스 의존 없이 scope_type으로 판별)
        return $this->roles()
            ->whereIn('scope_type', [
                \App\Enums\ScopeType::PLATFORM->value,
                \App\Enums\ScopeType::SYSTEM->value,
            ])
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
}
