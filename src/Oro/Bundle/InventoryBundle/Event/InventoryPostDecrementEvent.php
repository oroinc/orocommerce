<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Oro\Bundle\InventoryBundle\Event\InventoryQuantityChangeEvent;

class InventoryPostDecrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro_inventory.after.decrement';
}
