<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutGroupShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;

class CheckoutGroupShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    /** @var CheckoutLineItemGroupsShippingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemGroupsShippingManager;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var GroupLineItemHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupLineItemHelper;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var CheckoutGroupShippingMethodsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->checkoutLineItemGroupsShippingManager = $this->createMock(CheckoutLineItemGroupsShippingManager::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->groupLineItemHelper = $this->createMock(GroupLineItemHelperInterface::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);

        $this->provider = new CheckoutGroupShippingMethodsProvider(
            $this->checkoutShippingMethodsProvider,
            $this->checkoutLineItemGroupsShippingManager,
            $this->checkoutFactory,
            $this->groupLineItemHelper,
            $this->organizationProvider
        );
    }

    private function getCheckoutLineItem(int $id, Organization $organization, Checkout $checkout): CheckoutLineItem
    {
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects(self::any())
            ->method('getId')
            ->willReturn($id);
        $lineItem->expects(self::any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects(self::any())
            ->method('getCheckout')
            ->willReturn($checkout);

        return $lineItem;
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testGetGroupedApplicableMethodsViewsWithEmptyLineItems(): void
    {
        $this->checkoutShippingMethodsProvider->expects(self::never())
            ->method('getApplicableMethodsViews');

        $this->checkoutFactory->expects(self::never())
            ->method('createCheckout');

        self::assertEquals([], $this->provider->getGroupedApplicableMethodsViews(new Checkout(), []));
    }

    public function testGetGroupedApplicableMethodsViewsWhenLineItemsAreGroupedNotByOrganization(): void
    {
        $organization = $this->getOrganization(10);
        $checkout = new Checkout();
        $lineItem1 = $this->getCheckoutLineItem(1, $organization, $checkout);
        $checkout->addLineItem($lineItem1);
        $lineItem2 = $this->getCheckoutLineItem(2, $organization, $checkout);
        $checkout->addLineItem($lineItem2);
        $lineItem3 = $this->getCheckoutLineItem(3, $organization, $checkout);
        $checkout->addLineItem($lineItem3);

        $checkoutForGroup1 = new Checkout();
        $checkoutForGroup2 = new Checkout();

        $groupedLineItemIds = [
            'product.category:10' => [$lineItem1->getId(), $lineItem2->getId()],
            'product.category:20' => [$lineItem3->getId()]
        ];

        $availableShippingMethodsForGroup1 = new ShippingMethodViewCollection();
        $availableShippingMethodsForGroup1->addMethodView('test_shipping_1', []);
        $availableShippingMethodsForGroup1->addMethodTypeView(
            'test_shipping_1',
            'test_shipping_1',
            ['primary' => ['identifier' => 'primary']]
        );
        $availableShippingMethodsForGroup2 = new ShippingMethodViewCollection();
        $availableShippingMethodsForGroup2->addMethodView('test_shipping_2', []);
        $availableShippingMethodsForGroup2->addMethodTypeView(
            'test_shipping_2',
            'test_shipping_2',
            ['primary' => ['identifier' => 'primary']]
        );

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.category');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.category')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');

        $this->checkoutShippingMethodsProvider->expects(self::exactly(2))
            ->method('getApplicableMethodsViews')
            ->withConsecutive(
                [self::identicalTo($checkoutForGroup1)],
                [self::identicalTo($checkoutForGroup2)]
            )
            ->willReturnOnConsecutiveCalls(
                $availableShippingMethodsForGroup1,
                $availableShippingMethodsForGroup2
            );

        $this->checkoutFactory->expects(self::exactly(2))
            ->method('createCheckout')
            ->withConsecutive(
                [self::identicalTo($checkout), [$lineItem1, $lineItem2]],
                [self::identicalTo($checkout), [$lineItem3]]
            )
            ->willReturnOnConsecutiveCalls(
                $checkoutForGroup1,
                $checkoutForGroup2
            );

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            [
                'product.category:10' => $availableShippingMethodsForGroup1->toArray(),
                'product.category:20' => $availableShippingMethodsForGroup2->toArray()
            ],
            $this->provider->getGroupedApplicableMethodsViews($checkout, $groupedLineItemIds)
        );
    }


    public function testGetGroupedApplicableMethodsViewsWhenLineItemsAreGroupedByOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization1 = $this->getOrganization(10);
        $organization2 = $this->getOrganization(20);
        $checkout = new Checkout();
        $lineItem1 = $this->getCheckoutLineItem(1, $organization1, $checkout);
        $checkout->addLineItem($lineItem1);
        $lineItem2 = $this->getCheckoutLineItem(2, $organization1, $checkout);
        $checkout->addLineItem($lineItem2);
        $lineItem3 = $this->getCheckoutLineItem(3, $organization2, $checkout);
        $checkout->addLineItem($lineItem3);

        $checkoutForOrganization1 = new Checkout();
        $checkoutForOrganization2 = new Checkout();

        $groupedLineItemIds = [
            'product.organization:10' => [$lineItem1->getId(), $lineItem2->getId()],
            'product.organization:20' => [$lineItem3->getId()]
        ];

        $availableShippingMethodsForOrganization1 = new ShippingMethodViewCollection();
        $availableShippingMethodsForOrganization1->addMethodView('test_shipping_1', []);
        $availableShippingMethodsForOrganization1->addMethodTypeView(
            'test_shipping_1',
            'test_shipping_1',
            ['primary' => ['identifier' => 'primary']]
        );
        $availableShippingMethodsForOrganization2 = new ShippingMethodViewCollection();
        $availableShippingMethodsForOrganization2->addMethodView('test_shipping_2', []);
        $availableShippingMethodsForOrganization2->addMethodTypeView(
            'test_shipping_2',
            'test_shipping_2',
            ['primary' => ['identifier' => 'primary']]
        );

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.organization');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.organization')
            ->willReturn(true);
        $this->groupLineItemHelper->expects(self::any())
            ->method('getGroupingFieldValue')
            ->willReturnCallback(function (CheckoutLineItem $lineItem) {
                return $lineItem->getProduct()->getOrganization();
            });

        $this->checkoutShippingMethodsProvider->expects(self::exactly(2))
            ->method('getApplicableMethodsViews')
            ->withConsecutive(
                [self::identicalTo($checkoutForOrganization1)],
                [self::identicalTo($checkoutForOrganization2)]
            )
            ->willReturnOnConsecutiveCalls(
                $availableShippingMethodsForOrganization1,
                $availableShippingMethodsForOrganization2
            );

        $this->checkoutFactory->expects(self::exactly(2))
            ->method('createCheckout')
            ->withConsecutive(
                [self::identicalTo($checkout), [$lineItem1, $lineItem2]],
                [self::identicalTo($checkout), [$lineItem3]]
            )
            ->willReturnOnConsecutiveCalls(
                $checkoutForOrganization1,
                $checkoutForOrganization2
            );

        $this->organizationProvider->expects(self::exactly(2))
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(4))
            ->method('setOrganization')
            ->withConsecutive(
                [self::identicalTo($organization1)],
                [self::identicalTo($previousOrganization)],
                [self::identicalTo($organization2)],
                [self::identicalTo($previousOrganization)]
            );

        self::assertEquals(
            [
                'product.organization:10' => $availableShippingMethodsForOrganization1->toArray(),
                'product.organization:20' => $availableShippingMethodsForOrganization2->toArray()
            ],
            $this->provider->getGroupedApplicableMethodsViews($checkout, $groupedLineItemIds)
        );
    }

    public function testGetCurrentShippingMethods(): void
    {
        $checkout = new Checkout();
        $shippingMethods = ['product.category:1' => ['method' => 'method1', 'type' => 'type1']];

        $this->checkoutLineItemGroupsShippingManager->expects(self::once())
            ->method('getCheckoutLineItemGroupsShippingData')
            ->with(self::identicalTo($checkout))
            ->willReturn($shippingMethods);

        self::assertEquals($shippingMethods, $this->provider->getCurrentShippingMethods($checkout));
    }
}
