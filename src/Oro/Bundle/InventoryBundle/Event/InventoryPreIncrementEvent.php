<?php

namespace Oro\Bundle\InventoryBundle\Event;

class InventoryPreIncrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro_inventory.pre.increment';
}
