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

    /**
     * @param int $id
     * @param float $quantity
     * @param string $unitCode
     */
    public function __construct(int $id, float $quantity, string $unitCode)
    {
        $this->id = $id;
        $this->quantity = $quantity;
        $this->unitCode = $unitCode;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @return string
     */
    public function getUnitCode(): string
    {
        return $this->unitCode;
    }
}
