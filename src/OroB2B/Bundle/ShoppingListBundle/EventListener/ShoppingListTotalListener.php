<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\Registry;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceListsUpdateEvent;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

class ShoppingListTotalListener
{
    /**
     * ShoppingListTotalListener constructor.
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param CombinedPriceListsUpdateEvent $event
     */
    public function onPriceListUpdate(CombinedPriceListsUpdateEvent $event)
    {
        $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal')
            ->invalidateByCpl($event->getCombinedPriceListIds());
    }
}
