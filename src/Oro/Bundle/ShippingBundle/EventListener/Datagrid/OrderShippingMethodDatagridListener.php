<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Datagrid;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class OrderShippingMethodDatagridListener
{
    const SHIPPING_METHOD_COLUMN = 'shippingMethod';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $column = [
           // 'label' => 'oro.order.shipping_method.label',
            'label' => 'shipping method',
            'type' => 'twig',
            'template' => 'OroShippingBundle:Datagrid:Column/shippingMethodFull.html.twig',
            'frontend_type' => 'html',
        ];

        $columns = $config->offsetGetByPath("[columns]");
        $columns = array_merge(
            array_slice($columns, 0, 13),
            array(static::SHIPPING_METHOD_COLUMN => $column),
            array_slice($columns, 13, null)
        );
        $config->offsetSetByPath("[columns]", $columns);
    }
}
