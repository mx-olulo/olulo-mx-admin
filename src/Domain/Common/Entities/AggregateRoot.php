<?php

declare(strict_types=1);

namespace Olulo\Domain\Common\Entities;

/**
 * DDD Aggregate Root 추상 클래스
 *
 * 모든 애그리거트 루트 엔티티의 기본 클래스
 * 도메인 이벤트 관리 및 비즈니스 규칙 검증을 담당
 */
abstract class AggregateRoot
{
    /**
     * 도메인 이벤트 목록
     *
     * @var array<DomainEvent>
     */
    private array $domainEvents = [];

    /**
     * 엔티티 ID
     */
    protected mixed $id;

    /**
     * 생성 일시
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * 수정 일시
     */
    protected ?\DateTimeImmutable $updatedAt = null;

    /**
     * 테넌트 ID (멀티테넌시 지원)
     */
    protected ?string $tenantId = null;

    /**
     * 엔티티 ID 반환
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * 테넌트 ID 반환
     */
    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * 테넌트 ID 설정
     */
    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    /**
     * 도메인 이벤트 발생
     */
    protected function raiseEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * 발생한 도메인 이벤트 반환 및 초기화
     *
     * @return array<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * 생성 일시 반환
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * 수정 일시 반환
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * 수정 일시 갱신
     */
    protected function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * 엔티티 동일성 검증
     */
    public function equals(AggregateRoot $other): bool
    {
        return static::class === get_class($other)
            && $this->getId() === $other->getId();
    }

    /**
     * 비즈니스 규칙 검증 (하위 클래스에서 구현)
     */
    abstract protected function validate(): void;
}