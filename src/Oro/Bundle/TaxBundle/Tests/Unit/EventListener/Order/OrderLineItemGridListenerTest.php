<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\EventListener\Order\OrderLineItemGridListener;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $settingsProvider;

    /** @var OrderLineItemGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->listener = new OrderLineItemGridListener(
            $this->settingsProvider,
            TaxValue::class
        );
    }

    public function testOnBuildBeforeTaxesDisabled()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);

        $dataGrid = $this->createMock(DatagridInterface::class);
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(['name' => 'order-line-items-grid'], $gridConfig->toArray());
    }

    public function testOnBuildBeforeWithoutFormPart()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);

        $dataGrid = $this->createMock(DatagridInterface::class);
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->listener->onBuildBefore($event);

        $this->assertEquals(['name' => 'order-line-items-grid'], $gridConfig->toArray());
    }

    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid-frontend']);
        $from = ['alias' => 'orders', 'table' => OrderLineItem::class];
        $gridConfig->offsetSetByPath('[source][query][from]', [$from]);

        $dataGrid = $this->createMock(DatagridInterface::class);
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->settingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

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
                                    'join' => TaxValue::class,
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
                        'template' => '@OroTax/Order/Datagrid/Property/unitIncludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'unitPriceExcludingTax' => [
                        'label' => 'oro.tax.order_line_item.unitPrice.excludingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => '@OroTax/Order/Datagrid/Property/unitExcludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'unitPriceTaxAmount' => [
                        'label' => 'oro.tax.order_line_item.unitPrice.taxAmount.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => '@OroTax/Order/Datagrid/Property/unitTaxAmount.html.twig',
                        'renderable' => false,
                    ],
                    'rowTotalIncludingTax' => [
                        'label' => 'oro.tax.order_line_item.rowTotal.includingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => '@OroTax/Order/Datagrid/Property/rowIncludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'rowTotalExcludingTax' => [
                        'label' => 'oro.tax.order_line_item.rowTotal.excludingTax.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => '@OroTax/Order/Datagrid/Property/rowExcludingTax.html.twig',
                        'renderable' => false,
                    ],
                    'rowTotalTaxAmount' => [
                        'label' => 'oro.tax.order_line_item.rowTotal.taxAmount.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => '@OroTax/Order/Datagrid/Property/rowTaxAmount.html.twig',
                        'renderable' => false,
                    ],
                    'taxes' => [
                        'label' => 'oro.tax.order_line_item.taxes.label',
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'data_name' => 'result',
                        'template' => '@OroTax/Order/Datagrid/taxes.html.twig',
                        'renderable' => false,
                    ],
                ],
            ],
            $gridConfig->toArray()
        );
    }
}
