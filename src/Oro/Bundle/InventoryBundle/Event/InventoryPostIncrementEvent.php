<?php

namespace Oro\Bundle\InventoryBundle\Event;

class InventoryPostIncrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro_inventory.post.increment';
}
