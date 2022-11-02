<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

trait InventoryFallbackTrait
{
    /** @var EntityFieldFallbackValue */
    protected $minimumQuantityToOrder;

    /** @var EntityFieldFallbackValue */
    protected $maximumQuantityToOrder;

    /** @var EntityFieldFallbackValue */
    protected $highlightLowInventory;

    /** @var EntityFieldFallbackValue */
    protected $backOrder;

    /** @var EntityFieldFallbackValue */
    protected $decrementQuantity;

    /** @var EntityFieldFallbackValue */
    protected $inventoryThreshold;

    /** @var EntityFieldFallbackValue */
    protected $lowInventoryThreshold;

    /** @var EntityFieldFallbackValue */
    protected $manageInventory;

    /** @var EntityFieldFallbackValue */
    protected $isUpcoming;

    /** @var \DateTime|null */
    protected $availabilityDate;

    /**
     * @return EntityFieldFallbackValue
     */
    public function getMinimumQuantityToOrder()
    {
        return $this->minimumQuantityToOrder;
    }

    /**
     * @param EntityFieldFallbackValue $minimumQuantityToOrder
     *
     * @return $this
     */
    public function setMinimumQuantityToOrder(EntityFieldFallbackValue $minimumQuantityToOrder)
    {
        $this->minimumQuantityToOrder = $minimumQuantityToOrder;

        return $this;
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
     *
     * @return $this
     */
    public function setMaximumQuantityToOrder(EntityFieldFallbackValue $maximumQuantityToOrder)
    {
        $this->maximumQuantityToOrder = $maximumQuantityToOrder;

        return $this;
    }

    public function setHighlightLowInventory(EntityFieldFallbackValue $highlightLowInventory)
    {
        $this->highlightLowInventory = $highlightLowInventory;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getHighlightLowInventory()
    {
        return $this->highlightLowInventory;
    }

    public function setBackOrder(EntityFieldFallbackValue $backOrder)
    {
        $this->backOrder = $backOrder;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getBackOrder()
    {
        return $this->backOrder;
    }

    public function setDecrementQuantity(EntityFieldFallbackValue $decrementQuantity)
    {
        $this->decrementQuantity = $decrementQuantity;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getDecrementQuantity()
    {
        return $this->decrementQuantity;
    }

    public function setInventoryThreshold(EntityFieldFallbackValue $inventoryThreshold)
    {
        $this->inventoryThreshold = $inventoryThreshold;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getInventoryThreshold()
    {
        return $this->inventoryThreshold;
    }

    public function setLowInventoryThreshold(EntityFieldFallbackValue $lowInventoryThreshold)
    {
        $this->lowInventoryThreshold = $lowInventoryThreshold;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getLowInventoryThreshold()
    {
        return $this->lowInventoryThreshold;
    }

    public function setManageInventory(EntityFieldFallbackValue $manageInventory)
    {
        $this->manageInventory = $manageInventory;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getManageInventory()
    {
        return $this->manageInventory;
    }

    public function setIsUpcoming(EntityFieldFallbackValue $isUpcoming)
    {
        $this->isUpcoming = $isUpcoming;
    }

    /**
     * @return EntityFieldFallbackValue
     */
    public function getIsUpcoming()
    {
        return $this->isUpcoming;
    }

    /**
     * @param \DateTime|null $availabilityDate
     */
    public function setAvailabilityDate($availabilityDate)
    {
        $this->availabilityDate = $availabilityDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getAvailabilityDate()
    {
        return $this->availabilityDate;
    }
}
