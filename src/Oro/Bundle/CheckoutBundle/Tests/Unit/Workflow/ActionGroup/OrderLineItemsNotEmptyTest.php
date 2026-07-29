<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmpty;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLineItemsNotEmptyTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private OrderLineItemsNotEmpty $actionGroup;

    #[\Override]
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
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    ['attribute' => $lineItems, 'reasons_attribute' => []]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals([
            'orderLineItems' => $lineItems,
            'orderLineItemsNotEmpty' => true,
            'orderLineItemsForRfp' => [],
            'orderLineItemsNotEmptyForRfp' => true,
            'orderLineItemsSkippedReasons' => [],
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
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    [
                        'attribute' => $lineItems,
                        'reasons_attribute' => [CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH]
                    ]
                ],
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_rfp.frontend_product_visibility',
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    ['attribute' => $lineItemsRfp, 'reasons_attribute' => []]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals([
            'orderLineItems' => $lineItems,
            'orderLineItemsNotEmpty' => false,
            'orderLineItemsForRfp' => $lineItemsRfp,
            'orderLineItemsNotEmptyForRfp' => true,
            'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH],
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
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    ['attribute' => $lineItems, 'reasons_attribute' => [CheckoutLineItemsManager::REASON_NO_PRICE]]
                ],
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_rfp.frontend_product_visibility',
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    ['attribute' => $lineItemsRfp, 'reasons_attribute' => []]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals([
            'orderLineItems' => $lineItems,
            'orderLineItemsNotEmpty' => false,
            'orderLineItemsForRfp' => $lineItemsRfp,
            'orderLineItemsNotEmptyForRfp' => false,
            'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_NO_PRICE],
        ], $result);
    }

    public function testExecuteExposesSkippedReasonsFromOrderCallOnly()
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
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    [
                        'attribute' => $lineItems,
                        'reasons_attribute' => [
                            CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH,
                            CheckoutLineItemsManager::REASON_NO_PRICE,
                        ],
                    ]
                ],
                [
                    'get_order_line_items',
                    [
                        'checkout' => $checkout,
                        'disable_price_filter' => false,
                        'config_visibility_path' => 'oro_rfp.frontend_product_visibility',
                        'attribute' => null,
                        'reasons_attribute' => null
                    ],
                    [
                        'attribute' => $lineItemsRfp,
                        'reasons_attribute' => [CheckoutLineItemsManager::REASON_UNSUPPORTED_STATUS],
                    ]
                ]
            ]);

        $result = $this->actionGroup->execute($checkout);

        $this->assertEquals(
            [CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH, CheckoutLineItemsManager::REASON_NO_PRICE],
            $result['orderLineItemsSkippedReasons']
        );
    }
}
