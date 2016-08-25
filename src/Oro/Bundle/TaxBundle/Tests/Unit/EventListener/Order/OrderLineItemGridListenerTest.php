<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\EventListener\Order\OrderLineItemGridListener;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderLineItemGridListener */
    protected $listener;

    /** @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $settingsProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new OrderLineItemGridListener(
            $this->settingsProvider,
            'Oro\Bundle\TaxBundle\Entity\TaxValue'
        );
    }

    public function testOnBuildBeforeTaxesDisabled()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(['name' => 'order-line-items-grid'], $gridConfig->toArray());
    }

    public function testOnBuildBeforeWithoutFormPart()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(['name' => 'order-line-items-grid'], $gridConfig->toArray());
    }

    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid-frontend']);
        $from = ['alias' => 'orders', 'table' => 'Oro\Bundle\OrderBundle\Entity\OrderLineItem'];
        $gridConfig->offsetSetByPath('[source][query][from]', [$from]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'name' => 'order-line-items-grid-frontend',
                'source' => [
                    'query' => [
                        'from' => [$from],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'Oro\Bundle\TaxBundle\Entity\TaxValue',
                                    'alias' => 'taxValue',
                                    'conditionType' => 'WITH',
                                    'condition' => 'taxValue.entityClass = ' .
                                        '\'Oro\Bundle\OrderBundle\Entity\OrderLineItem\' AND ' .
                                        'taxValue.entityId = orders.id',
                                ],
                            ],
                        ],
                        'select' => ['taxValue.result'],
                    ],
                ],
                'columns' => [
                    'unitPriceIncludingTax' => [
                        'label' => 'oro.tax.order_line_item.unitPrice.includingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle:Order:Datagrid/Property/unitIncludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'unitPriceExcludingTax' => [
                        'label' => 'oro.tax.order_line_item.unitPrice.excludingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle:Order:Datagrid/Property/unitExcludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'unitPriceTaxAmount' => [
                        'label' => 'oro.tax.order_line_item.unitPrice.taxAmount.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle:Order:Datagrid/Property/unitTaxAmount.html.twig',
                        'renderable' => false,
                    ],
                    'rowTotalIncludingTax' => [
                        'label' => 'oro.tax.order_line_item.rowTotal.includingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle:Order:Datagrid/Property/rowIncludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'rowTotalExcludingTax' => [
                        'label' => 'oro.tax.order_line_item.rowTotal.excludingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle:Order:Datagrid/Property/rowExcludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'rowTotalTaxAmount' => [
                        'label' => 'oro.tax.order_line_item.rowTotal.taxAmount.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle:Order:Datagrid/Property/rowTaxAmount.html.twig',
                        'renderable' => false,
                    ],
                    'taxes' => [
                        'label' => 'oro.tax.order_line_item.taxes.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => 'OroTaxBundle::Order/Datagrid/taxes.html.twig',
                        'renderable' => false,
                    ],
                ],
            ],
            $gridConfig->toArray()
        );
    }
}
