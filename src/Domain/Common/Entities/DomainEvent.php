<?php

declare(strict_types=1);

namespace Olulo\Domain\Common\Entities;

/**
 * 도메인 이벤트 인터페이스
 *
 * 모든 도메인 이벤트가 구현해야 하는 기본 인터페이스
 */
interface DomainEvent
{
    /**
     * 이벤트 발생 일시
     */
    public function occurredAt(): \DateTimeImmutable;

    /**
     * 이벤트 이름
     */
    public function eventName(): string;

    /**
     * 애그리거트 ID
     */
    public function aggregateId(): mixed;

    /**
     * 테넌트 ID
     */
    public function tenantId(): ?string;

    /**
     * 이벤트 페이로드
     *
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * 이벤트 버전
     */
    public function version(): string;
}