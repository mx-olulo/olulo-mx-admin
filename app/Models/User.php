<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
            'password' => 'hashed',
            'firebase_claims' => 'array',
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
}
