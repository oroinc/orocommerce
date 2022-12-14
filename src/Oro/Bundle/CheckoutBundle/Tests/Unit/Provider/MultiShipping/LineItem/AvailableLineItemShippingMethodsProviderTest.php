<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\AvailableLineItemShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AvailableLineItemShippingMethodsProviderTest extends TestCase
{
    private CheckoutShippingMethodsProviderInterface|MockObject $shippingMethodsProvider;
    private DefaultMultipleShippingMethodProvider|MockObject $multipleShippingMethodsProvider;
    private CheckoutFactoryInterface|MockObject $checkoutFactory;
    private AvailableLineItemShippingMethodsProvider $provider;

    protected function setUp(): void
    {
        $this->shippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->multipleShippingMethodsProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);

        $this->provider = new AvailableLineItemShippingMethodsProvider(
            $this->shippingMethodsProvider,
            $this->multipleShippingMethodsProvider,
            $this->checkoutFactory
        );
    }

    public function testGetAvailableShippingMethods()
    {
        $this->multipleShippingMethodsProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn(['multi_shipping_1', 'multi_shipping_2']);

        $availableShippingMethods = [
            'test_shipping_1' => [
                'identifier' => 'test_shipping_1',
                'types' => ['primary' => ['identifier' => 'primary']]
            ],
            'multi_shipping_1' => [
                'identifier' => 'multi_shipping_1',
                'types' => ['multi_shipping_type' => ['identifier' => 'multi_shipping_type']]
            ],
            'multi_shipping_2' => [
                'identifier' => 'multi_shipping_2',
                'types' => ['multi_shipping_type' => ['identifier' => 'multi_shipping_type']]
            ],
            'test_shipping_3' => [
                'identifier' => 'test_shipping_3',
                'types' => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $shippingMethodViewCollectionMock = $this->createMock(ShippingMethodViewCollection::class);
        $shippingMethodViewCollectionMock->expects($this->once())
            ->method('toArray')
            ->willReturn($availableShippingMethods);

        $this->shippingMethodsProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->willReturn($shippingMethodViewCollectionMock);

        $this->multipleShippingMethodsProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $lineItem = new CheckoutLineItem();
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $result = $this->provider->getAvailableShippingMethods($lineItem);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('test_shipping_1', $result);
        $this->assertArrayHasKey('test_shipping_3', $result);
        $this->assertArrayNotHasKey('multi_shipping_1', $result);
        $this->assertArrayNotHasKey('multi_shipping_2', $result);
    }

    public function testGetAvailableShippingMethodsWhenMultiShippingMethodsNotConfigured()
    {
        $this->multipleShippingMethodsProvider->expects($this->never())
            ->method('getShippingMethods');

        $availableShippingMethods = [
            'test_shipping_1' => [
                'identifier' => 'test_shipping_1',
                'types' => ['primary' => ['identifier' => 'primary']]
            ],
            'test_shipping_2' => [
                'identifier' => 'test_shipping_2',
                'types' => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $shippingMethodViewCollectionMock = $this->createMock(ShippingMethodViewCollection::class);
        $shippingMethodViewCollectionMock->expects($this->once())
            ->method('toArray')
            ->willReturn($availableShippingMethods);

        $this->shippingMethodsProvider->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->willReturn($shippingMethodViewCollectionMock);

        $this->multipleShippingMethodsProvider->expects($this->once())
            ->method('hasShippingMethods')
            ->willReturn(false);

        $lineItem = new CheckoutLineItem();
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $result = $this->provider->getAvailableShippingMethods($lineItem);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('test_shipping_1', $result);
        $this->assertArrayHasKey('test_shipping_2', $result);
    }
}
