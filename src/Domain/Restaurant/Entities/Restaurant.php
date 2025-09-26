<?php

declare(strict_types=1);

namespace Olulo\Domain\Restaurant\Entities;

use Olulo\Domain\Common\Entities\AggregateRoot;
use Olulo\Domain\Restaurant\ValueObjects\RestaurantId;
use Olulo\Domain\Restaurant\ValueObjects\RestaurantName;
use Olulo\Domain\Restaurant\ValueObjects\Address;
use Olulo\Domain\Restaurant\ValueObjects\BusinessHours;
use Olulo\Domain\Restaurant\ValueObjects\DeliveryZone;
use Olulo\Domain\Restaurant\Events\RestaurantCreated;
use Olulo\Domain\Restaurant\Events\RestaurantStatusChanged;

/**
 * 레스토랑 애그리거트 루트
 *
 * 음식점 정보와 운영 정책을 관리하는 핵심 도메인 엔티티
 */
class Restaurant extends AggregateRoot
{
    /**
     * 레스토랑 상태 상수
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    private RestaurantName $name;
    private string $slug;
    private Address $address;
    private BusinessHours $businessHours;
    private DeliveryZone $deliveryZone;
    private string $status;
    private ?string $description;
    private array $cuisineTypes;
    private array $paymentMethods;
    private float $minimumOrderAmount;
    private float $deliveryFee;
    private int $averageDeliveryTime;
    private float $rating;
    private int $totalOrders;
    private bool $isVerified;
    private array $settings;

    /**
     * 새 레스토랑 생성
     */
    public static function create(
        RestaurantId $id,
        string $tenantId,
        RestaurantName $name,
        string $slug,
        Address $address,
        BusinessHours $businessHours,
        DeliveryZone $deliveryZone,
        array $cuisineTypes = [],
        float $minimumOrderAmount = 0.0,
        float $deliveryFee = 0.0
    ): self {
        $restaurant = new self();
        $restaurant->id = $id;
        $restaurant->tenantId = $tenantId;
        $restaurant->name = $name;
        $restaurant->slug = $slug;
        $restaurant->address = $address;
        $restaurant->businessHours = $businessHours;
        $restaurant->deliveryZone = $deliveryZone;
        $restaurant->status = self::STATUS_PENDING_APPROVAL;
        $restaurant->cuisineTypes = $cuisineTypes;
        $restaurant->minimumOrderAmount = $minimumOrderAmount;
        $restaurant->deliveryFee = $deliveryFee;
        $restaurant->averageDeliveryTime = 30;
        $restaurant->rating = 0.0;
        $restaurant->totalOrders = 0;
        $restaurant->isVerified = false;
        $restaurant->paymentMethods = ['cash', 'card'];
        $restaurant->settings = [];
        $restaurant->createdAt = new \DateTimeImmutable();

        $restaurant->validate();

        $restaurant->raiseEvent(new RestaurantCreated(
            $id,
            $tenantId,
            $name->getValue(),
            $slug
        ));

        return $restaurant;
    }

    /**
     * 레스토랑 상태 변경
     */
    public function changeStatus(string $newStatus, string $reason = ''): void
    {
        $validStatuses = [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_PENDING_APPROVAL,
        ];

        if (!in_array($newStatus, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid restaurant status: {$newStatus}");
        }

        $previousStatus = $this->status;
        $this->status = $newStatus;
        $this->updateTimestamp();

        $this->raiseEvent(new RestaurantStatusChanged(
            $this->id,
            $this->tenantId,
            $previousStatus,
            $newStatus,
            $reason
        ));
    }

    /**
     * 영업 시간 업데이트
     */
    public function updateBusinessHours(BusinessHours $businessHours): void
    {
        $this->businessHours = $businessHours;
        $this->updateTimestamp();
    }

    /**
     * 배달 구역 업데이트
     */
    public function updateDeliveryZone(DeliveryZone $deliveryZone): void
    {
        $this->deliveryZone = $deliveryZone;
        $this->updateTimestamp();
    }

    /**
     * 현재 영업 중인지 확인
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->businessHours->isOpenNow();
    }

    /**
     * 특정 주소로 배달 가능한지 확인
     */
    public function canDeliverTo(Address $address): bool
    {
        return $this->isOpen()
            && $this->deliveryZone->contains($address);
    }

    /**
     * 비즈니스 규칙 검증
     */
    protected function validate(): void
    {
        if ($this->minimumOrderAmount < 0) {
            throw new \InvalidArgumentException('Minimum order amount cannot be negative');
        }

        if ($this->deliveryFee < 0) {
            throw new \InvalidArgumentException('Delivery fee cannot be negative');
        }

        if ($this->averageDeliveryTime < 0) {
            throw new \InvalidArgumentException('Average delivery time cannot be negative');
        }

        if (empty($this->paymentMethods)) {
            throw new \InvalidArgumentException('At least one payment method is required');
        }
    }

    // Getters
    public function getName(): RestaurantName
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMinimumOrderAmount(): float
    {
        return $this->minimumOrderAmount;
    }

    public function getDeliveryFee(): float
    {
        return $this->deliveryFee;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }
}