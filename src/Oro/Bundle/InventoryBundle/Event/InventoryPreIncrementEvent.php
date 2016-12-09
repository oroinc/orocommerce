<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Oro\Bundle\InventoryBundle\Event\InventoryQuantityChangeEvent;

class InventoryPreIncrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro.inventory.pre.increment';
}
