<?php

declare(strict_types=1);

namespace Olulo\Domain\Common\ValueObjects;

/**
 * Value Object 추상 클래스
 *
 * 불변성과 값 동등성을 보장하는 기본 클래스
 */
abstract class ValueObject
{
    /**
     * 다른 Value Object와 동일한지 비교
     */
    public function equals(ValueObject $other): bool
    {
        return static::class === get_class($other)
            && $this->equalsCore($other);
    }

    /**
     * 하위 클래스에서 구현할 동등성 비교 로직
     */
    abstract protected function equalsCore(ValueObject $other): bool;

    /**
     * Value Object의 문자열 표현
     */
    abstract public function __toString(): string;

    /**
     * Value Object 검증
     */
    abstract protected function validate(): void;
}