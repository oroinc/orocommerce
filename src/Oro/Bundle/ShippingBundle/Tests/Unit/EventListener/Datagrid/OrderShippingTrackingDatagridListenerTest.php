<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShippingBundle\EventListener\Datagrid\OrderShippingTrackingDatagridListener;

class OrderShippingTrackingDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatagridConfiguration
     */
    protected $config;

    /**
     * @var OrderShippingTrackingDatagridListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new OrderShippingTrackingDatagridListener();
    }

    /**
     * @dataProvider onBuildBeforeDataProvider
     * @param array $columnsBefore
     * @param array $columnsAfter
     */
    public function testOnBuildBefore(array $columnsBefore, array $columnsAfter)
    {
        $config = DatagridConfiguration::create(['columns' => $columnsBefore]);
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $config));

        static::assertEquals(['columns' => $columnsAfter], $config->toArray());
    }

    /**
     * @return array
     */
    public function onBuildBeforeDataProvider()
    {
        $method = [
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => 'OroShippingBundle:Datagrid:Column/orderShippingTrackingMethod.html.twig'
        ];
        $number = [
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => 'OroShippingBundle:Datagrid:Column/orderShippingTrackingLink.html.twig'
        ];

        return [
            [
                'columnsBefore' => [],
                'columnsAfter' => [],
            ],
            [
                'columnsBefore' => [OrderShippingTrackingDatagridListener::TRACKING_NUMBER => []],
                'columnsAfter' => [OrderShippingTrackingDatagridListener::TRACKING_NUMBER => $number],
            ],
            [
                'columnsBefore' => [OrderShippingTrackingDatagridListener::SHIPPING_METHOD => []],
                'columnsAfter' => [OrderShippingTrackingDatagridListener::SHIPPING_METHOD => $method],
            ],
            [
                'columnsBefore' => [
                    OrderShippingTrackingDatagridListener::TRACKING_NUMBER => [],
                    OrderShippingTrackingDatagridListener::SHIPPING_METHOD => []
                ],
                'columnsAfter' => [
                    OrderShippingTrackingDatagridListener::TRACKING_NUMBER => $number,
                    OrderShippingTrackingDatagridListener::SHIPPING_METHOD => $method
                ],
            ]
        ];
    }
}
