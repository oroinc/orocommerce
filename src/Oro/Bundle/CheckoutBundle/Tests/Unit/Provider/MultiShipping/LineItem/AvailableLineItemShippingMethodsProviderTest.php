<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\AvailableLineItemShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

class AvailableLineItemShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodsProvider;

    /** @var DefaultMultipleShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingMethodProvider;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var AvailableLineItemShippingMethodsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->shippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->multiShippingMethodProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);

        $this->provider = new AvailableLineItemShippingMethodsProvider(
            $this->shippingMethodsProvider,
            $this->multiShippingMethodProvider,
            $this->checkoutFactory,
            $this->organizationProvider
        );
    }

    private function getShippingMethodViewCollection(array $shippingMethodViews): ShippingMethodViewCollection
    {
        $shippingMethodViewCollection = $this->createMock(ShippingMethodViewCollection::class);
        $shippingMethodViewCollection->expects(self::any())
            ->method('toArray')
            ->willReturn($shippingMethodViews);

        return $shippingMethodViewCollection;
    }

    public function testGetAvailableShippingMethods(): void
    {
        $organization = $this->createMock(Organization::class);
        $product = new Product();
        $product->setOrganization($organization);
        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('getShippingMethods')
            ->willReturn(['multi_shipping_1', 'multi_shipping_2']);

        $availableShippingMethods = [
            'test_shipping_1'  => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ],
            'multi_shipping_1' => [
                'identifier' => 'multi_shipping_1',
                'types'      => ['multi_shipping_type' => ['identifier' => 'multi_shipping_type']]
            ],
            'multi_shipping_2' => [
                'identifier' => 'multi_shipping_2',
                'types'      => ['multi_shipping_type' => ['identifier' => 'multi_shipping_type']]
            ],
            'test_shipping_3'  => [
                'identifier' => 'test_shipping_3',
                'types'      => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [null]);

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('hasShippingMethods')
            ->willReturn(true);

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $expectedAvailableShippingMethods = $availableShippingMethods;
        unset(
            $expectedAvailableShippingMethods['multi_shipping_1'],
            $expectedAvailableShippingMethods['multi_shipping_2']
        );
        self::assertEquals(
            $expectedAvailableShippingMethods,
            $this->provider->getAvailableShippingMethods($lineItem)
        );
        // test memory cache
        self::assertEquals(
            $expectedAvailableShippingMethods,
            $this->provider->getAvailableShippingMethods($lineItem)
        );
    }

    public function testGetAvailableShippingMethodsWhenMultiShippingMethodsNotConfigured(): void
    {
        $organization = $this->createMock(Organization::class);
        $product = new Product();
        $product->setOrganization($organization);
        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);

        $this->multiShippingMethodProvider->expects(self::never())
            ->method('getShippingMethods');

        $availableShippingMethods = [
            'test_shipping_1' => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ],
            'test_shipping_2' => [
                'identifier' => 'test_shipping_2',
                'types'      => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [null]);

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->multiShippingMethodProvider->expects(self::once())
            ->method('hasShippingMethods')
            ->willReturn(false);

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        self::assertEquals($availableShippingMethods, $this->provider->getAvailableShippingMethods($lineItem));
        // test memory cache
        self::assertEquals($availableShippingMethods, $this->provider->getAvailableShippingMethods($lineItem));
    }

    public function testResetMemoryCache(): void
    {
        $organization = $this->createMock(Organization::class);
        $product = new Product();
        $product->setOrganization($organization);
        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);

        $availableShippingMethods = [
            'test_shipping_1' => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ]
        ];

        $this->organizationProvider->expects(self::exactly(4))
            ->method('setOrganization')
            ->withConsecutive([$organization], [null], [$organization], [null]);

        $this->shippingMethodsProvider->expects(self::exactly(2))
            ->method('getApplicableMethodsViews')
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->multiShippingMethodProvider->expects(self::exactly(2))
            ->method('hasShippingMethods')
            ->willReturn(false);

        $this->checkoutFactory->expects(self::exactly(2))
            ->method('createCheckout')
            ->willReturn($checkout);

        self::assertEquals($availableShippingMethods, $this->provider->getAvailableShippingMethods($lineItem));
        // test memory cache
        self::assertEquals($availableShippingMethods, $this->provider->getAvailableShippingMethods($lineItem));
        // test reset memory cache
        $this->provider->reset();
        self::assertEquals($availableShippingMethods, $this->provider->getAvailableShippingMethods($lineItem));
    }
}
