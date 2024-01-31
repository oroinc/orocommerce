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
        string $checksum = '',
        ?string $shippingMethod = null,
        ?string $shippingMethodType = null
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        $lineItem->setProductSku($sku);
        $lineItem->setProductUnitCode($unitCode);
        $lineItem->setChecksum($checksum);
        $lineItem->setShippingMethod($shippingMethod);
        $lineItem->setShippingMethodType($shippingMethodType);

        return $lineItem;
    }

    public function testUpdateLineItemsShippingMethods(): void
    {
        $lineItem1 = $this->createLineItem('sku-1', 'item');
        $lineItem2 = $this->createLineItem('sku-2', 'set');
        $lineItem2_1 = $this->createLineItem('sku-2', 'set', 'sample_checksum');
        $lineItem3 = $this->createLineItem('sku-3', 'item');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem2_1);
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem2_1, $lineItem3]));

        $data = [
            'sku-1:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
            'sku-2:set:' => [
                'method' => 'SHIPPING_METHOD_2',
                'type' => 'SHIPPING_METHOD_TYPE_2',
            ],
            'sku-2:set:sample_checksum' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
            'sku-4:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
        ];

        $this->manager->updateLineItemsShippingMethods($data, $checkout);

        self::assertEquals('SHIPPING_METHOD', $lineItem1->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE', $lineItem1->getShippingMethodType());

        self::assertEquals('SHIPPING_METHOD_2', $lineItem2->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE_2', $lineItem2->getShippingMethodType());

        self::assertEquals('SHIPPING_METHOD', $lineItem2_1->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE', $lineItem2_1->getShippingMethodType());

        self::assertEmpty($lineItem3->getShippingMethod());
        self::assertEmpty($lineItem3->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWithDefaultsExists(): void
    {
        $lineItem1 = $this->createLineItem('sku-1', 'item');
        $lineItem3 = $this->createLineItem('sku-3', 'item');
        $lineItem3_1 = $this->createLineItem('sku-3', 'item', 'sample_checksum');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem3);
        $checkout->addLineItem($lineItem3_1);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem3, $lineItem3_1]));

        $availableShippingMethods = [
            'flat_rate_1' => [
                'identifier' => 'flat_rate_1',
                'types' => [
                    'primary' => [
                        'identifier' => 'primary',
                    ],
                ],
            ],
            'flat_rate_2' => [
                'identifier' => 'flat_rate_2',
                'types' => [
                    'primary' => [
                        'identifier' => 'primary_2',
                    ],
                ],
            ],
        ];

        $this->lineItemShippingMethodsProvider->expects(self::exactly(2))
            ->method('getAvailableShippingMethods')
            ->withConsecutive([$lineItem3], [$lineItem3_1])
            ->willReturn($availableShippingMethods);

        $data = [
            'sku-1:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
            'sku-4:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
        ];

        $this->manager->updateLineItemsShippingMethods($data, $checkout, true);

        self::assertEquals('SHIPPING_METHOD', $lineItem1->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE', $lineItem1->getShippingMethodType());

        self::assertEquals('flat_rate_1', $lineItem3->getShippingMethod());
        self::assertEquals('primary', $lineItem3->getShippingMethodType());

        self::assertEquals('flat_rate_1', $lineItem3_1->getShippingMethod());
        self::assertEquals('primary', $lineItem3_1->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWithEmptyDefaults(): void
    {
        $lineItem1 = $this->createLineItem('sku-1', 'item');
        $lineItem1_1 = $this->createLineItem('sku-1', 'item', 'sample_checksum');
        $lineItem3 = $this->createLineItem('sku-3', 'item');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem1_1);
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem1_1, $lineItem3]));

        $this->lineItemShippingMethodsProvider->expects(self::once())
            ->method('getAvailableShippingMethods')
            ->with($lineItem3)
            ->willReturn([]);

        $data = [
            'sku-1:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
            'sku-1:item:sample_checksum' => [
                'method' => 'SHIPPING_METHOD_2',
                'type' => 'SHIPPING_METHOD_TYPE_2',
            ],
            'sku-4:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
        ];

        $this->manager->updateLineItemsShippingMethods($data, $checkout, true);

        self::assertEquals('SHIPPING_METHOD', $lineItem1->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE', $lineItem1->getShippingMethodType());

        self::assertEquals('SHIPPING_METHOD_2', $lineItem1_1->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE_2', $lineItem1_1->getShippingMethodType());

        self::assertEmpty($lineItem3->getShippingMethod());
        self::assertEmpty($lineItem3->getShippingMethodType());
    }

    public function testGetCheckoutLineItemsShippingData(): void
    {
        $lineItem1 = $this->createLineItem('sku-1', 'item', '', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');
        $lineItem2 = $this->createLineItem('sku-2', 'set', '', 'SHIPPING_METHOD_2', 'SHIPPING_METHOD_TYPE_2');
        $lineItem2_1 = $this->createLineItem(
            'sku-2',
            'set',
            'sample_checksum',
            'SHIPPING_METHOD_2_1',
            'SHIPPING_METHOD_TYPE_2_1'
        );

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem2_1);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem2_1]));

        $expected = [
            'sku-1:item:' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE',
            ],
            'sku-2:set:' => [
                'method' => 'SHIPPING_METHOD_2',
                'type' => 'SHIPPING_METHOD_TYPE_2',
            ],
            'sku-2:set:sample_checksum' => [
                'method' => 'SHIPPING_METHOD_2_1',
                'type' => 'SHIPPING_METHOD_TYPE_2_1',
            ],
        ];

        $result = $this->manager->getCheckoutLineItemsShippingData($checkout);
        self::assertEquals($expected, $result);
    }

    public function testGetLineItemIdentifier(): void
    {
        $lineItem = $this->createLineItem('sku-1', 'item');
        $key = $this->manager->getLineItemIdentifier($lineItem);

        self::assertEquals('sku-1:item:', $key);
    }

    public function testGetLineItemIdentifierWithChecksum(): void
    {
        $lineItem = $this->createLineItem('sku-1', 'item', 'sample_checksum');
        $key = $this->manager->getLineItemIdentifier($lineItem);

        self::assertEquals('sku-1:item:sample_checksum', $key);
    }

    public function testUpdateLineItemsShippingPrices(): void
    {
        $lineItem1 = $this->createLineItem('sku-1', 'item', '', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');
        $lineItem2 = $this->createLineItem('sku-2', 'set', '', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');
        $lineItem3 = $this->createLineItem('sku-3', 'item');
        $lineItem4 = $this->createLineItem('sku-4', 'set', '', 'SHIPPING_METHOD', 'SHIPPING_METHOD_TYPE');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem3);
        $checkout->addLineItem($lineItem4);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3, $lineItem4]));

        $this->shippingPricePriceProvider->expects(self::exactly(3))
            ->method('getPrice')
            ->willReturnMap([
                [$lineItem1, Price::create(10.00, 'USD')],
                [$lineItem2, Price::create(7.00, 'EUR')],
                [$lineItem4, null],
            ]);

        $this->manager->updateLineItemsShippingPrices($checkout);

        self::assertEquals(10.00, $lineItem1->getShippingEstimateAmount());
        self::assertEquals('USD', $lineItem1->getCurrency());

        self::assertEquals(7.00, $lineItem2->getShippingEstimateAmount());
        self::assertEquals('EUR', $lineItem2->getCurrency());

        self::assertNull($lineItem3->getShippingEstimateAmount());
        self::assertNull($lineItem3->getCurrency());

        self::assertNull($lineItem4->getShippingEstimateAmount());
        self::assertNull($lineItem4->getCurrency());
    }
}
