<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmpty;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLineItemsNotEmptyTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private OrderLineItemsNotEmpty $actionGroup;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        $this->actionGroup = new OrderLineItemsNotEmpty($this->actionExecutor);
    }

    public function testExecuteWithOrderLineItems()
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItems = new ArrayCollection([$this->createMock(OrderLineItem::class)]);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->willReturnMap([
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_order.frontend_product_visibility',
                        'attribute' => null
                    ],
                    ['attribute' => $lineItems]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals([
            'orderLineItems' => $lineItems,
            'orderLineItemsNotEmpty' => true,
            'orderLineItemsForRfp' => [],
            'orderLineItemsNotEmptyForRfp' => true,
        ], $result);
    }

    public function testExecuteWithoutOrderLineItems()
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItems = new ArrayCollection([]);
        $lineItemsRfp = new ArrayCollection([$this->createMock(OrderLineItem::class)]);

        $this->actionExecutor->expects($this->exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_order.frontend_product_visibility',
                        'attribute' => null
                    ],
                    ['attribute' => $lineItems]
                ],
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_rfp.frontend_product_visibility',
                        'attribute' => null
                    ],
                    ['attribute' => $lineItemsRfp]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals([
            'orderLineItems' => $lineItems,
            'orderLineItemsNotEmpty' => false,
            'orderLineItemsForRfp' => $lineItemsRfp,
            'orderLineItemsNotEmptyForRfp' => true,
        ], $result);
    }

    public function testExecuteWithNoOrderLineItemsForBothCases()
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItems = new ArrayCollection([]);
        $lineItemsRfp = new ArrayCollection([]);

        $this->actionExecutor->expects($this->exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_order.frontend_product_visibility',
                        'attribute' => null
                    ],
                    ['attribute' => $lineItems]
                ],
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_rfp.frontend_product_visibility',
                        'attribute' => null
                    ],
                    ['attribute' => $lineItemsRfp]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals([
            'orderLineItems' => $lineItems,
            'orderLineItemsNotEmpty' => false,
            'orderLineItemsForRfp' => $lineItemsRfp,
            'orderLineItemsNotEmptyForRfp' => false,
        ], $result);
    }
}
