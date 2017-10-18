<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub;

trait InventoryFallbackTrait
{
    /** @var   */
    protected $minimumQuantityToOrder;

    /** @var   */
    protected $maximumQuantityToOrder;

    /** @var   */
    protected $highlightLowInventory;

    /** @var   */
    protected $backOrder;

    /** @var   */
    protected $decrementQuantity;

    /** @var   */
    protected $inventoryThreshold;

    /** @var   */
    protected $lowInventoryThreshold;

    /** @var   */
    protected $manageInventory;

    /**
     * @return mixed
     */
    public function getMinimumQuantityToOrder()
    {
        return $this->minimumQuantityToOrder;
    }

    /**
     * @param mixed $minimumQuantityToOrder
     * @return $this
     */
    public function setMinimumQuantityToOrder($minimumQuantityToOrder)
    {
        $this->minimumQuantityToOrder = $minimumQuantityToOrder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaximumQuantityToOrder()
    {
        return $this->maximumQuantityToOrder;
    }

    /**
     * @param mixed $maximumQuantityToOrder
     * @return $this
     */
    public function setMaximumQuantityToOrder($maximumQuantityToOrder)
    {
        $this->maximumQuantityToOrder = $maximumQuantityToOrder;

        return $this;
    }

    /**
     * @param $highlightLowInventory
     */
    public function setHighlightLowInventory($highlightLowInventory)
    {
        $this->highlightLowInventory = $highlightLowInventory;
    }

    /**
     * @return mixed
     */
    public function getHighlightLowInventory()
    {
        return $this->highlightLowInventory;
    }

    /**
     * @param $backOrder
     */
    public function setBackOrder($backOrder)
    {
        $this->backOrder = $backOrder;
    }

    /**
     * @return mixed
     */
    public function getBackOrder()
    {
        return $this->backOrder;
    }

    /**
     * @param $decrementQuantity
     */
    public function setDecrementQuantity($decrementQuantity)
    {
        $this->decrementQuantity = $decrementQuantity;
    }

    /**
     * @return mixed
     */
    public function getDecrementQuantity()
    {
        return $this->decrementQuantity;
    }

    /**
     * @param $inventoryThreshold
     */
    public function setInventoryThreshold($inventoryThreshold)
    {
        $this->inventoryThreshold = $inventoryThreshold;
    }

    /**
     * @return mixed
     */
    public function getInventoryThreshold()
    {
        return $this->inventoryThreshold;
    }

    /**
     * @param $lowInventoryThreshold
     */
    public function setLowInventoryThreshold($lowInventoryThreshold)
    {
        $this->lowInventoryThreshold = $lowInventoryThreshold;
    }

    /**
     * @return mixed
     */
    public function getLowInventoryThreshold()
    {
        return $this->lowInventoryThreshold;
    }

    /**
     * @param $manageInventory
     */
    public function setManageInventory($manageInventory)
    {
        $this->manageInventory = $manageInventory;
    }

    /**
     * @return mixed
     */
    public function getManageInventory()
    {
        return $this->manageInventory;
    }
}
