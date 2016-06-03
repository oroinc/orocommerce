<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\Registry;

use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
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

    /**
     * @param AccountCPLUpdateEvent $event
     */
    public function onAccountPriceListUpdate(AccountCPLUpdateEvent $event)
    {
        // Update shopping list totals by account
    }

    /**
     * @param AccountGroupCPLUpdateEvent $event
     */
    public function onAccountGroupPriceListUpdate(AccountGroupCPLUpdateEvent $event)
    {
        // Update shopping list totals by account group
    }

    /**
     * @param WebsiteCPLUpdateEvent $event
     */
    public function onWebsitePriceListUpdate(WebsiteCPLUpdateEvent $event)
    {
        // Update shopping list totals by website
    }

    /**
     * @param ConfigCPLUpdateEvent $event
     */
    public function onConfigPriceListUpdate(ConfigCPLUpdateEvent $event)
    {
        // Update shopping list totals by config
    }
}
