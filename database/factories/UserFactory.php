<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // 기본값: customer (마이그레이션 default와 동일)
            'user_type' => \App\Enums\UserType::CUSTOMER,
            'global_role' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Admin 타입 사용자 생성 (멀티테넌트 접근)
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => \App\Enums\UserType::ADMIN,
            'global_role' => null,
        ]);
    }

    /**
     * User 타입 사용자 생성 (글로벌 패널 접근)
     */
    public function user(?string $globalRole = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => \App\Enums\UserType::USER,
            'global_role' => $globalRole,
        ]);
    }

    /**
     * Platform Admin 생성
     */
    public function platformAdmin(): static
    {
        return $this->user('platform_admin');
    }

    /**
     * System Admin 생성
     */
    public function systemAdmin(): static
    {
        return $this->user('system_admin');
    }

    /**
     * Customer 타입 사용자 생성 (Firebase 인증)
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => \App\Enums\UserType::CUSTOMER,
            'global_role' => null,
            'firebase_uid' => 'firebase_' . fake()->uuid(),
        ]);
    }
}
