<?php

namespace Oro\Bundle\InventoryBundle\Event;

use Oro\Bundle\InventoryBundle\Event\DecrementEvent;

class InventoryAfterDecrementEvent extends DecrementEvent
{
    const NAME = 'oro_inventory.after.decrement';
}