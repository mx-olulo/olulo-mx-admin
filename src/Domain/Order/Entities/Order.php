<?php

declare(strict_types=1);

namespace Olulo\Domain\Order\Entities;

use Olulo\Domain\Common\Entities\AggregateRoot;
use Olulo\Domain\Order\ValueObjects\OrderId;
use Olulo\Domain\Order\ValueObjects\OrderNumber;
use Olulo\Domain\Order\ValueObjects\OrderStatus;
use Olulo\Domain\Order\ValueObjects\OrderTotal;
use Olulo\Domain\Order\ValueObjects\DeliveryAddress;
use Olulo\Domain\Order\Events\OrderPlaced;
use Olulo\Domain\Order\Events\OrderStatusChanged;
use Olulo\Domain\Order\Events\OrderCancelled;

/**
 * 주문 애그리거트 루트
 *
 * 음식 주문의 생명주기와 상태를 관리하는 핵심 도메인 엔티티
 */
class Order extends AggregateRoot
{
    /**
     * 주문 상태 상수
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    private OrderNumber $orderNumber;
    private string $restaurantId;
    private string $customerId;
    private OrderStatus $status;
    private DeliveryAddress $deliveryAddress;
    private array $items;
    private OrderTotal $total;
    private string $paymentMethod;
    private ?string $paymentTransactionId;
    private ?string $deliveryPersonId;
    private ?\DateTimeImmutable $estimatedDeliveryTime;
    private ?\DateTimeImmutable $actualDeliveryTime;
    private ?string $customerNote;
    private ?string $restaurantNote;
    private ?float $deliveryFee;
    private ?float $tipAmount;
    private array $metadata;

    /**
     * 새 주문 생성
     */
    public static function place(
        OrderId $id,
        string $tenantId,
        OrderNumber $orderNumber,
        string $restaurantId,
        string $customerId,
        DeliveryAddress $deliveryAddress,
        array $items,
        string $paymentMethod,
        ?string $customerNote = null
    ): self {
        $order = new self();
        $order->id = $id;
        $order->tenantId = $tenantId;
        $order->orderNumber = $orderNumber;
        $order->restaurantId = $restaurantId;
        $order->customerId = $customerId;
        $order->status = OrderStatus::pending();
        $order->deliveryAddress = $deliveryAddress;
        $order->items = $items;
        $order->paymentMethod = $paymentMethod;
        $order->customerNote = $customerNote;
        $order->createdAt = new \DateTimeImmutable();
        $order->metadata = [];

        $order->calculateTotal();
        $order->validate();

        $order->raiseEvent(new OrderPlaced(
            $id,
            $tenantId,
            $orderNumber->getValue(),
            $restaurantId,
            $customerId,
            $order->total->getAmount()
        ));

        return $order;
    }

    /**
     * 주문 확인
     */
    public function confirm(int $estimatedMinutes = 30): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Order can only be confirmed from pending status');
        }

        $this->status = OrderStatus::confirmed();
        $this->estimatedDeliveryTime = (new \DateTimeImmutable())->modify("+{$estimatedMinutes} minutes");
        $this->updateTimestamp();

        $this->raiseEvent(new OrderStatusChanged(
            $this->id,
            $this->tenantId,
            OrderStatus::STATUS_PENDING,
            OrderStatus::STATUS_CONFIRMED
        ));
    }

    /**
     * 주문 취소
     */
    public function cancel(string $reason, bool $refund = false): void
    {
        if (!$this->canBeCancelled()) {
            throw new \DomainException('Order cannot be cancelled in current status');
        }

        $previousStatus = $this->status->getValue();
        $this->status = OrderStatus::cancelled();
        $this->updateTimestamp();

        if ($refund && $this->paymentTransactionId) {
            $this->status = OrderStatus::refunded();
        }

        $this->raiseEvent(new OrderCancelled(
            $this->id,
            $this->tenantId,
            $reason,
            $refund
        ));
    }

    /**
     * 배달원 할당
     */
    public function assignDeliveryPerson(string $deliveryPersonId): void
    {
        if (!$this->status->isReadyForPickup()) {
            throw new \DomainException('Order must be ready for pickup before assigning delivery person');
        }

        $this->deliveryPersonId = $deliveryPersonId;
        $this->status = OrderStatus::outForDelivery();
        $this->updateTimestamp();

        $this->raiseEvent(new OrderStatusChanged(
            $this->id,
            $this->tenantId,
            OrderStatus::STATUS_READY_FOR_PICKUP,
            OrderStatus::STATUS_OUT_FOR_DELIVERY
        ));
    }

    /**
     * 배달 완료 처리
     */
    public function markAsDelivered(): void
    {
        if (!$this->status->isOutForDelivery()) {
            throw new \DomainException('Order must be out for delivery to mark as delivered');
        }

        $this->status = OrderStatus::delivered();
        $this->actualDeliveryTime = new \DateTimeImmutable();
        $this->updateTimestamp();

        $this->raiseEvent(new OrderStatusChanged(
            $this->id,
            $this->tenantId,
            OrderStatus::STATUS_OUT_FOR_DELIVERY,
            OrderStatus::STATUS_DELIVERED
        ));
    }

    /**
     * 취소 가능 여부 확인
     */
    private function canBeCancelled(): bool
    {
        return $this->status->isPending()
            || $this->status->isConfirmed()
            || $this->status->isPreparing();
    }

    /**
     * 총액 계산
     */
    private function calculateTotal(): void
    {
        $subtotal = 0.0;

        foreach ($this->items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $total = $subtotal + ($this->deliveryFee ?? 0) + ($this->tipAmount ?? 0);
        $this->total = new OrderTotal($total);
    }

    /**
     * 비즈니스 규칙 검증
     */
    protected function validate(): void
    {
        if (empty($this->items)) {
            throw new \InvalidArgumentException('Order must have at least one item');
        }

        if (empty($this->restaurantId)) {
            throw new \InvalidArgumentException('Restaurant ID is required');
        }

        if (empty($this->customerId)) {
            throw new \InvalidArgumentException('Customer ID is required');
        }

        if (empty($this->paymentMethod)) {
            throw new \InvalidArgumentException('Payment method is required');
        }
    }

    // Getters
    public function getOrderNumber(): OrderNumber
    {
        return $this->orderNumber;
    }

    public function getRestaurantId(): string
    {
        return $this->restaurantId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getTotal(): OrderTotal
    {
        return $this->total;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getEstimatedDeliveryTime(): ?\DateTimeImmutable
    {
        return $this->estimatedDeliveryTime;
    }
}