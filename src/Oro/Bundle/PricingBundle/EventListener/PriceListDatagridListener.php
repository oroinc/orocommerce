<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Add current time to grid parameters for grid column Active/inactive
 */
class PriceListDatagridListener
{
    public function onBuildBefore(BuildBefore $event)
    {
        $params = $event->getDatagrid()->getParameters();
        $params->set('now', new \DateTime('now', new \DateTimeZone('UTC')));
    }
}
