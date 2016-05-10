<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class PriceListDatagridListener
{
    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $params = $event->getDatagrid()->getParameters();
        $params->set('now', new \DateTime('now', new \DateTimeZone('UTC')));
    }
}
