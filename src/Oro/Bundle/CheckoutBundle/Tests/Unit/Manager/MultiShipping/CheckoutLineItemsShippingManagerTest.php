<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Manager\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\AvailableLineItemShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingPriceProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class CheckoutLineItemsShippingManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AvailableLineItemShippingMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemShippingMethodsProvider;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsProvider;

    /** @var LineItemShippingPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPricePriceProvider;

    /** @var CheckoutLineItemsShippingManager */
    private $manager;

    protected function setUp(): void
    {
        $this->lineItemShippingMethodsProvider = $this->createMock(AvailableLineItemShippingMethodsProvider::class);
        $this->lineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->shippingPricePriceProvider = $this->createMock(LineItemShippingPriceProviderInterface::class);

        $this->manager = new CheckoutLineItemsShippingManager(
            $this->lineItemShippingMethodsProvider,
            $this->lineItemsProvider,
            $this->shippingPricePriceProvider
        );
    }

    private function createLineItem(
        string $sku,
        string $unitCode,
        ?string $shippingMethod = null,
        ?string $shippingMethodType = null
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        $lineItem->setProductSku($sku);
        $lineItem->setProductUnitCode($unitCode);
        $lineItem->setShippingMethod($shippingMethod);
        $lineItem->setShippingMethodType($shippingMethodType);

        return $lineItem;
    }

    public function testUpdateLineItemsShippingMethods()
    {
        $checkout = new Checkout();

        $lineItem1 = $this->createLineItem('sku-1', 'item');
        $checkout->addLineItem($lineItem1);

        $lineItem2 = $this->createLineItem('sku-2', 'set');
        $checkout->addLineItem($lineItem2);

        $lineItem3 = $this->createLineItem('sku-3', 'item');
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $data = [
            'sku-1:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
            'sku-2:set' => [
                'method' => 'SHIPPING_METHOD_2',
                'type' => 'SHIPPING_METHOD_TYPE_2'
            ],
            'sku-4:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
        ];

        $this->manager->updateLineItemsShippingMethods($data, $checkout);

        $this->assertEquals('SHIPPING_METHOD', $lineItem1->getShippingMethod());
        $this->assertEquals('SHIPPING_METHOD_TYPE', $lineItem1->getShippingMethodType());

        $this->assertEquals('SHIPPING_METHOD_2', $lineItem2->getShippingMethod());
        $this->assertEquals('SHIPPING_METHOD_TYPE_2', $lineItem2->getShippingMethodType());

        $this->assertEmpty($lineItem3->getShippingMethod());
        $this->assertEmpty($lineItem3->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWithDefaultsExists()
    {
        $checkout = new Checkout();

        $lineItem1 = $this->createLineItem('sku-1', 'item');
        $checkout->addLineItem($lineItem1);

        $lineItem3 = $this->createLineItem('sku-3', 'item');
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem3]));

        $availableShippingMethods = [
            'flat_rate_1' => [
                'identifier' => 'flat_rate_1',
                'types' => [
                    'primary' => [
                        'identifier' => 'primary'
                    ]
                ]
            ],
            'flat_rate_2' => [
                'identifier' => 'flat_rate_2',
                'types' => [
                    'primary' => [
                        'identifier' => 'primary_2'
                    ]
                ]
            ],
        ];

        $this->lineItemShippingMethodsProvider->expects($this->once())
            ->method('getAvailableShippingMethods')
            ->with($lineItem3)
            ->willReturn($availableShippingMethods);

        $data = [
            'sku-1:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
            'sku-4:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
        ];

        $this->manager->updateLineItemsShippingMethods($data, $checkout, true);

        $this->assertEquals('SHIPPING_METHOD', $lineItem1->getShippingMethod());
        $this->assertEquals('SHIPPING_METHOD_TYPE', $lineItem1->getShippingMethodType());

        $this->assertEquals('flat_rate_1', $lineItem3->getShippingMethod());
        $this->assertEquals('primary', $lineItem3->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWithEmptyDefaults()
    {
        $checkout = new Checkout();

        $lineItem1 = $this->createLineItem('sku-1', 'item');
        $checkout->addLineItem($lineItem1);

        $lineItem3 = $this->createLineItem('sku-3', 'item');
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem3]));

        $this->lineItemShippingMethodsProvider->expects($this->once())
            ->method('getAvailableShippingMethods')
            ->with($lineItem3)
            ->willReturn([]);

        $data = [
            'sku-1:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
            'sku-4:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
        ];

        $this->manager->updateLineItemsShippingMethods($data, $checkout, true);

        $this->assertEquals('SHIPPING_METHOD', $lineItem1->getShippingMethod());
        $this->assertEquals('SHIPPING_METHOD_TYPE', $lineItem1->getShippingMethodType());

        $this->assertEmpty($lineItem3->getShippingMethod());
        $this->assertEmpty($lineItem3->getShippingMethodType());
    }

    public function testGetCheckoutLineItemsShippingData()
    {
        $checkout = new Checkout();

        $lineItem1 = $this->createLineItem('sku-1', 'item', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');
        $checkout->addLineItem($lineItem1);

        $lineItem2 = $this->createLineItem('sku-2', 'set', 'SHIPPING_METHOD_2', 'SHIPPING_METHOD_TYPE_2');
        $checkout->addLineItem($lineItem2);

        $this->lineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2]));

        $expected = [
            'sku-1:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ],
            'sku-2:set' => [
                'method' => 'SHIPPING_METHOD_2',
                'type' => 'SHIPPING_METHOD_TYPE_2'
            ],
        ];

        $result = $this->manager->getCheckoutLineItemsShippingData($checkout);
        $this->assertEquals($expected, $result);
    }

    public function testGetLineItemIdentifier()
    {
        $lineItem = $this->createLineItem('sku-1', 'item');
        $key = $this->manager->getLineItemIdentifier($lineItem);

        $this->assertEquals('sku-1:item', $key);
    }

    public function testUpdateLineItemsShippingPrices()
    {
        $checkout = new Checkout();

        $lineItem1 = $this->createLineItem('sku-1', 'item', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');
        $checkout->addLineItem($lineItem1);

        $lineItem2 = $this->createLineItem('sku-2', 'set', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');
        $checkout->addLineItem($lineItem2);

        $lineItem3 = $this->createLineItem('sku-3', 'item');
        $checkout->addLineItem($lineItem3);

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $this->shippingPricePriceProvider->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturnMap([
                [$lineItem1, Price::create(10.00, 'USD')],
                [$lineItem2, Price::create(7.00, 'USD')]
            ]);

        $this->manager->updateLineItemsShippingPrices($checkout);

        $this->assertNotNull($lineItem1->getShippingEstimateAmount());
        $this->assertNotNull($lineItem2->getShippingEstimateAmount());

        $this->assertEquals(10.00, $lineItem1->getShippingEstimateAmount());
        $this->assertEquals(7.00, $lineItem2->getShippingEstimateAmount());
        $this->assertNull($lineItem3->getShippingEstimateAmount());
    }
}
