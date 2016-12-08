<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Event\DecrementEvent;

class InventoryPreDecrementEvent extends DecrementEvent
{
    const NAME = 'oro_inventory.pre.decrement';

    protected $quantityToDecrement;

    public function __construct(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        parent::__construct($inventoryLevel);
        $this->quantityToDecrement = $quantityToDecrement;
    }
    /**
     * @return mixed
     */
    public function getQuantityToDecrement()
    {
        return $this->quantityToDecrement;
    }

    /**
     * @param mixed $quantityToDecrement
     */
    public function setQuantityToDecrement($quantityToDecrement)
    {
        $this->quantityToDecrement = $quantityToDecrement;
    }
}
