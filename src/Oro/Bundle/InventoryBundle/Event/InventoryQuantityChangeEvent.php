<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Symfony\Component\EventDispatcher\Event;

class InventoryQuantityChangeEvent extends Event
{
    protected $inventoryLevel;

    public function __construct(InventoryLevel $inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;
    }

    /**
     * @return \Extend\Entity\EX_OroInventoryBundle_InventoryLevel|InventoryLevel
     */
    public function getInventoryLevel()
    {
        return $this->inventoryLevel;
    }

    /**
     * @param \Extend\Entity\EX_OroInventoryBundle_InventoryLevel|InventoryLevel $inventoryLevel
     */
    public function setInventoryLevel($inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;
    }
}
