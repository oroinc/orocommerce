<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Oro\Bundle\InventoryBundle\Event\InventoryQuantityChangeEvent;

class InventoryPreDecrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro_inventory.pre.decrement';
}
