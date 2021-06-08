<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PromotionBundle\EventListener\OrderLineItemGridListener;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxationSettingsProvider;

    /**
     * @var OrderLineItemGridListener
     */
    protected $orderLineItemGridListener;

    protected function setUp(): void
    {
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->orderLineItemGridListener = new OrderLineItemGridListener($this->taxationSettingsProvider);
    }

    public function testOnBuildBefore()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid-frontend']);
        $from = ['alias' => 'orders', 'table' => 'Oro\Bundle\OrderBundle\Entity\OrderLineItem'];
        $gridConfig->offsetSetByPath('[source][query][from]', [$from]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->orderLineItemGridListener->onBuildBefore($event);

        $this->assertEquals(
            [
                'name' => 'order-line-items-grid-frontend',
                'source' =>
                    [
                        'query' =>
                            [
                                'from' =>
                                    [
                                        0 =>
                                            [
                                                'alias' => 'orders',
                                                'table' => 'Oro\\Bundle\\OrderBundle\\Entity\\OrderLineItem',
                                            ],
                                    ],
                                'select' =>
                                    [
                                        0 => '(SELECT SUM(discount.amount) FROM Oro\\Bundle\\Promoti'
                                            . 'onBundle\\Entity\\AppliedDiscount AS discount WHERE dis'
                                            . 'count.lineItem = orders.id) AS discountAmount',
                                    ],
                            ],
                    ],
                'columns' =>
                    [
                        'rowTotalDiscountAmount' =>
                            [
                                'label' => 'oro.order.view.order_line_item.row_total_discount_amount.label',
                                'type' => 'twig',
                                'frontend_type' => 'html',
                                'data_name' => 'discountAmount',
                                'template' => '@OroPromotion/Datagrid/Order/rowTotalDiscountAmount.html.twig',
                                'renderable' => false,
                            ],
                        'rowTotalAfterDiscountIncludingTax' =>
                            [
                                'label' =>
                                    'oro.order.view.order_line_item.row_total_after_discount_including_tax.label',
                                'type' => 'twig',
                                'frontend_type' => 'html',
                                'data_name' => 'discountAmount',
                                'template' => '@OroPromotion/Datagrid/Order/rowTotalAfterDiscountInclu'
                                    . 'dingTax.html.twig',
                                'renderable' => false,
                            ],
                        'rowTotalAfterDiscountExcludingTax' =>
                            [
                                'label' =>
                                    'oro.order.view.order_line_item.row_total_after_discount_excluding_tax.label',
                                'type' => 'twig',
                                'frontend_type' => 'html',
                                'data_name' => 'discountAmount',
                                'template' => '@OroPromotion/Datagrid/Order/rowTotalAfterDiscoun'
                                    . 'tExcludingTax.html.twig',
                                'renderable' => false,
                            ],
                    ],
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnBuildBeforeTaxesDisabled()
    {
        $gridConfig = DatagridConfiguration::create(['name' => 'order-line-items-grid']);
        $from = ['alias' => 'orders', 'table' => 'Oro\Bundle\OrderBundle\Entity\OrderLineItem'];
        $gridConfig->offsetSetByPath('[source][query][from]', [$from]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);
        $event = new BuildBefore($dataGrid, $gridConfig);

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->orderLineItemGridListener->onBuildBefore($event);

        $this->assertEquals(
            [
                'name' => 'order-line-items-grid',
                'source' =>
                    [
                        'query' =>
                            [
                                'from' =>
                                    [
                                        0 =>
                                            [
                                                'alias' => 'orders',
                                                'table' => 'Oro\\Bundle\\OrderBundle\\Entity\\OrderLineItem',
                                            ],
                                    ],
                                'select' =>
                                    [
                                        0 => '(SELECT SUM(discount.amount) FROM Oro\\Bundle\\Promotio'
                                            . 'nBundle\\Entity\\AppliedDiscount AS discount WHERE disco'
                                            . 'unt.lineItem = orders.id) AS discountAmount',
                                    ],
                            ],
                    ],
                'columns' =>
                    [
                        'rowTotalDiscountAmount' =>
                            [
                                'label' => 'oro.order.view.order_line_item.row_total_discount_amount.label',
                                'type' => 'twig',
                                'frontend_type' => 'html',
                                'data_name' => 'discountAmount',
                                'template' => '@OroPromotion/Datagrid/Order/rowTotalDiscountAmount.html.twig',
                                'renderable' => false,
                            ],
                        'rowTotalAfterDiscount' =>
                            [
                                'label' => 'oro.order.view.order_line_item.row_total_after_discount.label',
                                'type' => 'twig',
                                'frontend_type' => 'html',
                                'data_name' => 'discountAmount',
                                'template' => '@OroPromotion/Datagrid/Order/rowTotalAfterDiscount.html.twig',
                                'renderable' => false,
                            ],
                    ],
            ],
            $gridConfig->toArray()
        );
    }
}
