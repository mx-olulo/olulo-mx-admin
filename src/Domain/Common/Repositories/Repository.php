<?php

declare(strict_types=1);

namespace Olulo\Domain\Common\Repositories;

use Olulo\Domain\Common\Entities\AggregateRoot;

/**
 * 리포지토리 인터페이스
 *
 * 도메인 엔티티의 영속성 관리를 위한 기본 인터페이스
 */
interface Repository
{
    /**
     * ID로 엔티티 조회
     */
    public function findById(mixed $id, ?string $tenantId = null): ?AggregateRoot;

    /**
     * 엔티티 저장
     */
    public function save(AggregateRoot $entity): void;

    /**
     * 엔티티 삭제
     */
    public function delete(AggregateRoot $entity): void;

    /**
     * 다음 ID 생성
     */
    public function nextIdentity(): mixed;

    /**
     * 테넌트별 모든 엔티티 조회
     *
     * @return array<AggregateRoot>
     */
    public function findAllByTenant(string $tenantId): array;
}