<?php

namespace Oro\Bundle\OrderBundle\Api\Model;

/**
 * Represents an order subtotal.
 */
final readonly class OrderSubtotal
{
    private string $id;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        int $orderSubtotalNumber,
        private string $orderSubtotalType,
        private string $label,
        private int $orderId,
        private float $amount,
        private int|float $signedAmount,
        private ?string $currency,
        private ?int $priceListId,
        private bool $visible,
        private array $data
    ) {
        $this->id = sprintf('%s-%s-%s', $orderId, $orderSubtotalType, $orderSubtotalNumber);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrderSubtotalType(): string
    {
        return $this->orderSubtotalType;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getSignedAmount(): int|float
    {
        return $this->signedAmount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getPriceListId(): ?int
    {
        return $this->priceListId;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
