<?php

namespace Oro\Bundle\InventoryBundle\Event;

class InventoryPreIncrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro.inventory.pre.increment';
}
