<?php

namespace Oro\Bundle\InventoryBundle\Event;

class InventoryPostDecrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro_inventory.post.decrement';
}
