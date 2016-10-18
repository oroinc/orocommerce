<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShippingBundle\EventListener\Datagrid\OrderShippingMethodDatagridListener;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderShippingMethodDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DatagridConfiguration */
    protected $config;

    /** @var ProductShippingOptionsDatagridListener */
    protected $listener;

    public function setUp()
    {
        $this->config = DatagridConfiguration::create([]);
        $this->listener = new OrderShippingMethodDatagridListener($this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->config);
    }

    public function testOnBuildBefore()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $this->config));

        $this->assertEquals(
            [
                'columns' => [
                    'shippingMethod' => [
                        'label' => 'oro.shipping.methods.label',
                        'type' => 'twig',
                        'template' => 'OroShippingBundle:Datagrid:Column/shippingMethodFull.html.twig',
                        'frontend_type' => 'html',
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }
}
