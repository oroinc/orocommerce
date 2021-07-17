<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

/**
 * Stores data used to update line items.
 */
class LineItemModel
{
    /** @var int */
    private $id;

    /** @var float */
    private $quantity;

    /** @var string */
    private $unitCode;

    public function __construct(int $id, float $quantity, string $unitCode)
    {
        $this->id = $id;
        $this->quantity = $quantity;
        $this->unitCode = $unitCode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitCode(): string
    {
        return $this->unitCode;
    }
}
