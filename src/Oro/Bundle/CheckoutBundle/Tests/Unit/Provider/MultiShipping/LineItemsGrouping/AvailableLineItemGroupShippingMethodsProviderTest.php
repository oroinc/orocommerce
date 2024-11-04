<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\AvailableLineItemGroupShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;

class AvailableLineItemGroupShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodsProvider;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var GroupLineItemHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupLineItemHelper;

    /** @var AvailableLineItemGroupShippingMethodsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);
        $this->groupLineItemHelper = $this->createMock(GroupLineItemHelperInterface::class);

        $this->provider = new AvailableLineItemGroupShippingMethodsProvider(
            $this->shippingMethodsProvider,
            $this->checkoutFactory,
            $this->organizationProvider,
            $this->groupLineItemHelper
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
        $lineItemGroupKey = 'product.brand:1';
        $lineItem = new CheckoutLineItem();
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $checkoutToGetData = new Checkout();

        $availableShippingMethods = [
            'test_shipping_1'  => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ],
            'test_shipping_2'  => [
                'identifier' => 'test_shipping_2',
                'types'      => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.brand');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.brand')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');

        $this->organizationProvider->expects(self::never())
            ->method('setOrganization');

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), [$lineItem])
            ->willReturn($checkoutToGetData);

        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
        // test memory cache
        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
    }

    public function testGetAvailableShippingMethodsWhenLineItemsAreGroupedByOrganization(): void
    {
        $lineItemGroupKey = 'product.organization:1';
        $organization = $this->createMock(Organization::class);
        $product = new Product();
        $product->setOrganization($organization);
        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $checkoutToGetData = new Checkout();

        $availableShippingMethods = [
            'test_shipping_1'  => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ],
            'test_shipping_2'  => [
                'identifier' => 'test_shipping_2',
                'types'      => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.organization');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.organization')
            ->willReturn(true);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldValue')
            ->willReturnCallback(function (CheckoutLineItem $lineItem) {
                return $lineItem->getProduct()->getOrganization();
            });

        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [null]);

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), [$lineItem])
            ->willReturn($checkoutToGetData);

        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
        // test memory cache
        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
    }

    public function testGetAvailableShippingMethodsWhenLineItemsAreGroupedByOrganizationAndFreeFormProduct(): void
    {
        $lineItemGroupKey = 'other-items';
        $organization = $this->createMock(Organization::class);
        $product = new Product();
        $product->setOrganization($organization);
        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $checkoutToGetData = new Checkout();

        $availableShippingMethods = [
            'test_shipping_1'  => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ],
            'test_shipping_2'  => [
                'identifier' => 'test_shipping_2',
                'types'      => ['test_shipping_type' => ['identifier' => 'test_shipping_type']]
            ]
        ];

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.organization');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.organization')
            ->willReturn(true);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');

        $this->organizationProvider->expects(self::never())
            ->method('setOrganization');

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), [$lineItem])
            ->willReturn($checkoutToGetData);

        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
        // test memory cache
        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
    }

    public function testGetAvailableShippingMethodsWhenMultiShippingMethodsNotConfigured(): void
    {
        $lineItemGroupKey = 'product.brand:1';
        $lineItem = new CheckoutLineItem();
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $checkoutToGetData = new Checkout();

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

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.brand');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.brand')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');

        $this->organizationProvider->expects(self::never())
            ->method('setOrganization');

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), [$lineItem])
            ->willReturn($checkoutToGetData);

        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
        // test memory cache
        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
    }

    public function testResetMemoryCache(): void
    {
        $lineItemGroupKey = 'product.brand:1';
        $lineItem = new CheckoutLineItem();
        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $lineItem->setCheckout($checkout);
        $checkoutToGetData = new Checkout();

        $availableShippingMethods = [
            'test_shipping_1' => [
                'identifier' => 'test_shipping_1',
                'types'      => ['primary' => ['identifier' => 'primary']]
            ]
        ];

        $this->groupLineItemHelper->expects(self::exactly(2))
            ->method('getGroupingFieldPath')
            ->willReturn('product.brand');
        $this->groupLineItemHelper->expects(self::exactly(2))
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.brand')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');

        $this->organizationProvider->expects(self::never())
            ->method('setOrganization');

        $this->shippingMethodsProvider->expects(self::exactly(2))
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn($this->getShippingMethodViewCollection($availableShippingMethods));

        $this->checkoutFactory->expects(self::exactly(2))
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), [$lineItem])
            ->willReturn($checkoutToGetData);

        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
        // test memory cache
        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
        // test reset memory cache
        $this->provider->reset();
        self::assertEquals(
            $availableShippingMethods,
            $this->provider->getAvailableShippingMethods([$lineItem], $lineItemGroupKey)
        );
    }
}
