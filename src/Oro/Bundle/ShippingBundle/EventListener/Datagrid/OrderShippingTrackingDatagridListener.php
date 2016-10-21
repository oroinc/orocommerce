<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class OrderShippingTrackingDatagridListener
{
    const TRACKING_NUMBER = 'number';
    const SHIPPING_METHOD = 'method';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $columns = $config->offsetGetByPath('[columns]');
        if (is_array($columns)) {
            if (array_key_exists(self::TRACKING_NUMBER, $columns)) {
                $columns[self::TRACKING_NUMBER]['type'] = 'twig';
                $columns[self::TRACKING_NUMBER]['frontend_type'] = 'html';
                $columns[self::TRACKING_NUMBER]['template'] =
                    'OroShippingBundle:Datagrid:Column/orderShippingTrackingLink.html.twig';
            }
            if (array_key_exists(self::SHIPPING_METHOD, $columns)) {
                $columns[self::SHIPPING_METHOD]['type'] = 'twig';
                $columns[self::SHIPPING_METHOD]['frontend_type'] = 'html';
                $columns[self::SHIPPING_METHOD]['template'] =
                    'OroShippingBundle:Datagrid:Column/orderShippingTrackingMethod.html.twig';
            }
            $config->offsetSetByPath('[columns]', $columns);
        }
    }
}
