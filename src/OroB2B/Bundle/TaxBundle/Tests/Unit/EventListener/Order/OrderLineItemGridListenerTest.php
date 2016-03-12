<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\TaxBundle\EventListener\Order\OrderLineItemGridListener;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderLineItemGridListener */
    protected $listener;

    /** @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $settingsProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new OrderLineItemGridListener(
            $this->settingsProvider,
            'OroB2B\Bundle\TaxBundle\Entity\TaxValue'
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

    public function testOnBuildBeforeFrontendTaxesDisabled()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->listener->onBuildBeforeFrontend($event);

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

    public function testOnBuildBeforeFrontendWithoutFormPart()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->listener->onBuildBeforeFrontend($event);

        $this->assertEquals(['name' => 'order-line-items-grid'], $gridConfig->toArray());
    }

    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);
        $from = ['alias' => 'orders', 'table' => 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem'];
        $gridConfig->offsetSetByPath('[source][query][from]', [$from]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'name' => 'order-line-items-grid',
                'source' => [
                    'query' => [
                        'from' => [$from],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'OroB2B\Bundle\TaxBundle\Entity\TaxValue',
                                    'alias' => 'taxValue',
                                    'conditionType' => 'WITH',
                                    'condition' => 'taxValue.entityClass = ' .
                                        '\'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem\' AND ' .
                                        'taxValue.entityId = orders.id',
                                ],
                            ],
                        ],
                        'select' => ['taxValue.result'],
                    ],
                ],
                'columns' => [
                    'result' => [
                        'label' => 'orob2b.tax.result.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroB2BTaxBundle::Order/Datagrid/column.html.twig',
                    ],
                ],
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnBuildBeforeFrontend()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid-frontend']);
        $from = ['alias' => 'orders', 'table' => 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem'];
        $gridConfig->offsetSetByPath('[source][query][from]', [$from]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->listener->onBuildBeforeFrontend($event);

        $this->assertEquals(
            [
                'name' => 'order-line-items-grid-frontend',
                'source' => [
                    'query' => [
                        'from' => [$from],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'OroB2B\Bundle\TaxBundle\Entity\TaxValue',
                                    'alias' => 'taxValue',
                                    'conditionType' => 'WITH',
                                    'condition' => 'taxValue.entityClass = ' .
                                        '\'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem\' AND ' .
                                        'taxValue.entityId = orders.id',
                                ],
                            ],
                        ],
                        'select' => ['taxValue.result'],
                    ],
                ],
                'columns' => [
                    'unitPriceIncludingTax' => [
                        'label' => 'orob2b.tax.order_item_datagrid.unitPrice.includingTax.label',
                        'frontend_type' => 'string',
                        'data_name' => '[result][unit][includingTax]',
                        'renderable' => false
                    ],
                    'unitPriceExcludingTax' => [
                        'label' => 'orob2b.tax.order_item_datagrid.unitPrice.excludingTax.label',
                        'frontend_type' => 'string',
                        'data_name' => '[result][unit][excludingTax]',
                        'renderable' => false
                    ],
                    'unitPriceTaxAmount' => [
                        'label' => 'orob2b.tax.order_item_datagrid.unitPrice.taxAmount.label',
                        'frontend_type' => 'string',
                        'data_name' => '[result][unit][taxAmount]',
                        'renderable' => false
                    ],
                    'rowTotalIncludingTax' => [
                        'label' => 'orob2b.tax.order_item_datagrid.rowTotal.includingTax.label',
                        'frontend_type' => 'string',
                        'data_name' => '[result][row][includingTax]',
                        'renderable' => false
                    ],
                    'rowTotalExcludingTax' => [
                        'label' => 'orob2b.tax.order_item_datagrid.rowTotal.excludingTax.label',
                        'frontend_type' => 'string',
                        'data_name' => '[result][row][excludingTax]',
                        'renderable' => false
                    ],
                    'rowTotalTaxAmount' => [
                        'label' => 'orob2b.tax.order_item_datagrid.rowTotal.taxAmount.label',
                        'frontend_type' => 'string',
                        'data_name' => '[result][row][taxAmount]',
                        'renderable' => false
                    ],
                    'result' => [
                        'label' => 'orob2b.tax.result.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroB2BTaxBundle::Order/Datagrid/Frontend/column.html.twig',
                        'renderable' => false
                    ],
                ],
            ],
            $gridConfig->toArray()
        );
    }
}
