<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /** @var EntityFieldFallbackValue */
    private $manageInventory;

    /** @var EntityFieldFallbackValue */
    private $highlightLowInventory;

    /** @var EntityFieldFallbackValue */
    private $inventoryThreshold;

    /** @var EntityFieldFallbackValue */
    private $lowInventoryThreshold;

    /** @var EntityFieldFallbackValue */
    private $minimumQuantityToOrder;

    /** @var EntityFieldFallbackValue */
    private $maximumQuantityToOrder;

    /** @var EntityFieldFallbackValue */
    private $decrementQuantity;

    /** @var EntityFieldFallbackValue */
    private $backOrder;

    /** @var EntityFieldFallbackValue */
    private $isUpcoming;

    /** @var string|object|null */
    private $inventoryStatus;

    /**
     * @return string
     */
    public function getName()
    {
        return 'xxx';
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getManageInventory()
    {
        return $this->manageInventory;
    }

    /**
     * @param EntityFieldFallbackValue $manageInventory
     */
    public function setManageInventory($manageInventory)
    {
        $this->manageInventory = $manageInventory;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getHighlightLowInventory()
    {
        return $this->highlightLowInventory;
    }

    /**
     * @param EntityFieldFallbackValue $highlightLowInventory
     */
    public function setHighlightLowInventory($highlightLowInventory)
    {
        $this->highlightLowInventory = $highlightLowInventory;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getInventoryThreshold()
    {
        return $this->inventoryThreshold;
    }

    /**
     * @param EntityFieldFallbackValue $inventoryThreshold
     */
    public function setInventoryThreshold($inventoryThreshold)
    {
        $this->inventoryThreshold = $inventoryThreshold;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getLowInventoryThreshold()
    {
        return $this->lowInventoryThreshold;
    }

    /**
     * @param EntityFieldFallbackValue $lowInventoryThreshold
     */
    public function setLowInventoryThreshold($lowInventoryThreshold)
    {
        $this->lowInventoryThreshold = $lowInventoryThreshold;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getMinimumQuantityToOrder()
    {
        return $this->minimumQuantityToOrder;
    }

    /**
     * @param EntityFieldFallbackValue $minimumQuantityToOrder
     */
    public function setMinimumQuantityToOrder($minimumQuantityToOrder)
    {
        $this->minimumQuantityToOrder = $minimumQuantityToOrder;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getMaximumQuantityToOrder()
    {
        return $this->maximumQuantityToOrder;
    }

    /**
     * @param EntityFieldFallbackValue $maximumQuantityToOrder
     */
    public function setMaximumQuantityToOrder($maximumQuantityToOrder)
    {
        $this->maximumQuantityToOrder = $maximumQuantityToOrder;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getDecrementQuantity()
    {
        return $this->decrementQuantity;
    }

    /**
     * @param EntityFieldFallbackValue $decrementQuantity
     */
    public function setDecrementQuantity($decrementQuantity)
    {
        $this->decrementQuantity = $decrementQuantity;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getBackOrder()
    {
        return $this->backOrder;
    }

    /**
     * @param EntityFieldFallbackValue $backOrder
     */
    public function setBackOrder($backOrder)
    {
        $this->backOrder = $backOrder;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getIsUpcoming()
    {
        return $this->isUpcoming;
    }

    /**
     * @param EntityFieldFallbackValue $isUpcoming
     */
    public function setIsUpcoming($isUpcoming)
    {
        $this->isUpcoming = $isUpcoming;
    }

    /**
     * @return string|object|null
     */
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * @param string|object|null $inventoryStatus
     */
    public function setInventoryStatus($inventoryStatus): void
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
