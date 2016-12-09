<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

class InventoryQuantityChangeEvent extends Event
{
    /**
     * @var InventoryLevel
     */
    protected $inventoryLevel;

    /**
     * @var boolean
     */
    protected $quantityChanged;

    /**
     * @var
     */
    protected $quantityToChange;

    /**
     * @param InventoryLevel $inventoryLevel
     * @param $quantityToChange
     */
    public function __construct(InventoryLevel $inventoryLevel, $quantityToChange)
    {
        $this->inventoryLevel = $inventoryLevel;
        $this->quantityToChange = $quantityToChange;
        $this->quantityChanged = false;
    }

    /**
     * @return InventoryLevel
     */
    public function getInventoryLevel()
    {
        return $this->inventoryLevel;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @return $this
     */
    public function setInventoryLevel($inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuantityToChange()
    {
        return $this->quantityToChange;
    }

    /**
     * @param mixed $quantityToChange
     * @return $this
     */
    public function setQuantityToChange($quantityToChange)
    {
        $this->quantityToChange = $quantityToChange;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isQuantityChanged()
    {
        return $this->quantityChanged;
    }

    /**
     * @param boolean $quantityChanged
     * @return $this
     */
    public function setQuantityChanged($quantityChanged)
    {
        $this->quantityChanged = $quantityChanged;

        return $this;
    }
}
