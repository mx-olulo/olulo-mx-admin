<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
     */
    public function getFirebaseClaim(?string $key = null): mixed
    {
        $claims = $this->firebase_claims ?? [];

        if ($key === null) {
            return $claims;
        }

        return $claims[$key] ?? null;
    }

    /**
     * Filament 패널 접근 권한 확인
     *
     * @param  Panel  $panel  Filament 패널 인스턴스
     * @return bool 접근 가능 여부
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 현재는 모든 인증된 사용자가 접근 가능
        // 향후 역할 기반 접근 제어(RBAC) 구현 시 수정 필요
        // 예: return $this->hasRole('admin') || $this->hasRole('store_manager');
        return true;
    }

    /**
     * 사용자 로케일 가져오기 (없으면 기본값)
     */
    public function getLocaleAttribute(): string
    {
        return $this->attributes['locale'] ?? config('app.locale', 'es-MX');
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
     * @param  array  $firebaseUserData  Firebase에서 가져온 사용자 데이터
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
}
