<?php

declare(strict_types=1);

namespace Olulo\Domain\Restaurant\ValueObjects;

use Olulo\Domain\Common\ValueObjects\ValueObject;
use Ramsey\Uuid\Uuid;

/**
 * 레스토랑 ID Value Object
 *
 * UUID 기반의 레스토랑 고유 식별자
 */
final class RestaurantId extends ValueObject
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
        $this->validate();
    }

    /**
     * 새 ID 생성
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * 기존 ID로부터 생성
     */
    public static function fromString(string $value): self
    {
        return new self($value);
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
        if (!Uuid::isValid($this->value)) {
            throw new \InvalidArgumentException('Invalid Restaurant ID format');
        }
    }
}