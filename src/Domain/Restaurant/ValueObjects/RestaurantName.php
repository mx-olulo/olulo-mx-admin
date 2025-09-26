<?php

declare(strict_types=1);

namespace Olulo\Domain\Restaurant\ValueObjects;

use Olulo\Domain\Common\ValueObjects\ValueObject;

/**
 * 레스토랑 이름 Value Object
 *
 * 레스토랑 이름의 유효성과 불변성을 보장
 */
final class RestaurantName extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = trim($value);
        $this->validate();
    }

    /**
     * 값 반환
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 문자열 표현
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * 동등성 비교
     */
    protected function equalsCore(ValueObject $other): bool
    {
        return $this->value === $other->getValue();
    }

    /**
     * 유효성 검증
     */
    protected function validate(): void
    {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Restaurant name cannot be empty');
        }

        if (strlen($this->value) < 2) {
            throw new \InvalidArgumentException('Restaurant name must be at least 2 characters long');
        }

        if (strlen($this->value) > 100) {
            throw new \InvalidArgumentException('Restaurant name cannot exceed 100 characters');
        }
    }
}