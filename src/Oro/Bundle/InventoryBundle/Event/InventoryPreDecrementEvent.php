<?php

namespace Oro\Bundle\InventoryBundle\Event;

class InventoryPreDecrementEvent extends InventoryQuantityChangeEvent
{
    const NAME = 'oro_inventory.pre.decrement';
}
