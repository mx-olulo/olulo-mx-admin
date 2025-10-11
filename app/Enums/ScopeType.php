<?php

namespace App\Enums;

enum ScopeType: string
{
    case PLATFORM = 'PLATFORM';
    case SYSTEM = 'SYSTEM';
    case ORGANIZATION = 'ORG';
    case BRAND = 'BRAND';
    case STORE = 'STORE';

    /**
     * Panel ID 반환 (Filament Panel 경로용)
     * scope type을 소문자로 변환
     */
    public function getPanelId(): string
    {
        return strtolower($this->value);
    }

    /**
     * Panel ID로 ScopeType 찾기
     */
    public static function fromPanelId(string $panelId): ?self
    {
        $upperPanelId = strtoupper($panelId);

        return collect(self::cases())
            ->first(fn (self $case) => $case->value === $upperPanelId);
    }

    /**
     * 해당 scope의 모델 클래스 반환
     */
    public function getModelClass(): string
    {
        return match ($this) {
            self::PLATFORM => \App\Models\Platform::class,
            self::SYSTEM => \App\Models\System::class,
            self::ORGANIZATION => \App\Models\Organization::class,
            self::BRAND => \App\Models\Brand::class,
            self::STORE => \App\Models\Store::class,
        };
    }

    /**
     * 모든 scope type과 모델 클래스 매핑 반환 (morphMap용)
     *
     * @return array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function getMorphMap(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getModelClass()])
            ->toArray();
    }

    /**
     * 모든 유효한 scope type 값 반환
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
