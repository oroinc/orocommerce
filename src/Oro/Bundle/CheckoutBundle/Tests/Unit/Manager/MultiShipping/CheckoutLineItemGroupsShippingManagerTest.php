<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Manager\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\LineItemGroupShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostCalculator;

class CheckoutLineItemGroupsShippingManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LineItemGroupShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemGroupShippingMethodsProvider;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsProvider;

    /** @var MultiShippingCostCalculator|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostCalculator;

    /** @var GroupLineItemHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupLineItemHelper;

    /** @var CheckoutLineItemGroupsShippingManager */
    private $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemGroupShippingMethodsProvider = $this->createMock(
            LineItemGroupShippingMethodsProviderInterface::class
        );
        $this->lineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->shippingCostCalculator = $this->createMock(MultiShippingCostCalculator::class);
        $this->groupLineItemHelper = $this->createMock(GroupLineItemHelperInterface::class);

        $this->manager = new CheckoutLineItemGroupsShippingManager(
            $this->lineItemGroupShippingMethodsProvider,
            $this->lineItemsProvider,
            $this->shippingCostCalculator,
            $this->groupLineItemHelper
        );
    }

    public function testUpdateLineItemGroupsShippingMethods(): void
    {
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ]);
        $shippingData = ['product.category:1' => ['method' => 'method1', 'type' => 'type1']];

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with(self::identicalTo($checkout))
            ->willReturn($lineItems);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.category');
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($lineItems, 'product.category')
            ->willReturn([
                'product.category:1' => [$lineItem1],
                'product.category:2' => [$lineItem2]
            ]);

        $this->lineItemGroupShippingMethodsProvider->expects(self::never())
            ->method('getAvailableShippingMethods');

        $this->manager->updateLineItemGroupsShippingMethods($shippingData, $checkout);

        self::assertEquals($shippingData, $checkout->getLineItemGroupShippingData());
    }

    public function testUpdateLineItemGroupsShippingMethodsWhenShippingDataIsNull(): void
    {
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ]);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with(self::identicalTo($checkout))
            ->willReturn($lineItems);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.category');
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($lineItems, 'product.category')
            ->willReturn([
                'product.category:1' => [$lineItem1],
                'product.category:2' => [$lineItem2]
            ]);

        $this->lineItemGroupShippingMethodsProvider->expects(self::never())
            ->method('getAvailableShippingMethods');

        $this->manager->updateLineItemGroupsShippingMethods(null, $checkout);

        self::assertEquals([], $checkout->getLineItemGroupShippingData());
    }

    public function testUpdateLineItemGroupsShippingMethodsWithDefaultsExists(): void
    {
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ]);
        $shippingData = ['product.category:1' => ['method' => 'method1', 'type' => 'type1']];
        $expectedShippingData = [
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['method' => 'method1', 'type' => 'primary']
        ];

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with(self::identicalTo($checkout))
            ->willReturn($lineItems);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.category');
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($lineItems, 'product.category')
            ->willReturn([
                'product.category:1' => [$lineItem1],
                'product.category:2' => [$lineItem2]
            ]);

        $this->lineItemGroupShippingMethodsProvider->expects(self::once())
            ->method('getAvailableShippingMethods')
            ->with([$lineItem2], 'product.category:2')
            ->willReturn([
                'method1' => ['identifier' => 'method1', 'types' => ['primary' => ['identifier' => 'primary']]],
                'method2' => ['identifier' => 'method2', 'types' => ['primary' => ['identifier' => 'primary_2']]],
            ]);

        $this->manager->updateLineItemGroupsShippingMethods($shippingData, $checkout, true);

        self::assertEquals($expectedShippingData, $checkout->getLineItemGroupShippingData());
    }

    public function testUpdateLineItemGroupsShippingMethodsWithEmptyDefaults(): void
    {
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ]);
        $shippingData = ['product.category:1' => ['method' => 'method1', 'type' => 'type1']];

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with(self::identicalTo($checkout))
            ->willReturn($lineItems);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.category');
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($lineItems, 'product.category')
            ->willReturn([
                'product.category:1' => [$lineItem1],
                'product.category:2' => [$lineItem2]
            ]);

        $this->lineItemGroupShippingMethodsProvider->expects(self::once())
            ->method('getAvailableShippingMethods')
            ->with([$lineItem2], 'product.category:2')
            ->willReturn([]);

        $this->manager->updateLineItemGroupsShippingMethods($shippingData, $checkout, true);

        self::assertEquals($shippingData, $checkout->getLineItemGroupShippingData());
    }

    public function testGetCheckoutLineItemGroupsShippingData(): void
    {
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
            'product.category:2' => ['amount' => 2.0],
            'product.category:3' => ['method' => 'method3', 'type' => 'type3']
        ]);

        self::assertEquals(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
                'product.category:3' => ['method' => 'method3', 'type' => 'type3']
            ],
            $this->manager->getCheckoutLineItemGroupsShippingData($checkout)
        );
    }

    public function testUpdateLineItemGroupsShippingPrices(): void
    {
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();
        $lineItem3 = new CheckoutLineItem();
        $lineItem4 = new CheckoutLineItem();
        $lineItem5 = new CheckoutLineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2, $lineItem3, $lineItem4, $lineItem5]);
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
            'product.category:2' => ['amount' => 2.0],
            'product.category:3' => ['method' => 'method3', 'type' => 'type3'],
            'other-items'        => ['method' => 'method4', 'type' => 'type4']
        ]);

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.category');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.category')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($lineItems, 'product.category')
            ->willReturn([
                'product.category:1' => [$lineItem1, $lineItem2],
                'product.category:2' => [$lineItem3],
                'product.category:3' => [$lineItem4],
                'other-items'        => [$lineItem5]
            ]);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->shippingCostCalculator->expects(self::exactly(3))
            ->method('calculateShippingPrice')
            ->willReturnMap([
                [$checkout, [$lineItem1, $lineItem2], 'method1', 'type1', null, Price::create(10.0, 'USD')],
                [$checkout, [$lineItem4], 'method3', 'type3', null, null],
                [$checkout, [$lineItem5], 'method4', 'type4', null, Price::create(7.0, 'USD')]
            ]);

        $this->manager->updateLineItemGroupsShippingPrices($checkout);

        self::assertEquals(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 10.0],
                'product.category:3' => ['method' => 'method3', 'type' => 'type3'],
                'other-items'        => ['method' => 'method4', 'type' => 'type4', 'amount' => 7.0]
            ],
            $checkout->getLineItemGroupShippingData()
        );
    }

    public function testUpdateLineItemGroupsShippingPricesWhenLineItemsAreGroupedByOrganization(): void
    {
        $organization1 = new Organization();
        $organization3 = new Organization();
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();
        $lineItem3 = new CheckoutLineItem();
        $lineItem4 = new CheckoutLineItem();
        $lineItem5 = new CheckoutLineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2, $lineItem3, $lineItem4, $lineItem5]);
        $checkout = new Checkout();
        $checkout->setLineItemGroupShippingData([
            'product.organization:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
            'product.organization:2' => ['amount' => 2.0],
            'product.organization:3' => ['method' => 'method3', 'type' => 'type3'],
            'other-items'            => ['method' => 'method4', 'type' => 'type4']
        ]);

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.organization');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.organization')
            ->willReturn(true);
        $this->groupLineItemHelper->expects(self::exactly(2))
            ->method('getGroupingFieldValue')
            ->willReturnMap([
                [$lineItem1, 'product.organization', $organization1],
                [$lineItem4, 'product.organization', $organization3]
            ]);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($lineItems, 'product.organization')
            ->willReturn([
                'product.organization:1' => [$lineItem1, $lineItem2],
                'product.organization:2' => [$lineItem3],
                'product.organization:3' => [$lineItem4],
                'other-items'            => [$lineItem5]
            ]);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->shippingCostCalculator->expects(self::exactly(3))
            ->method('calculateShippingPrice')
            ->willReturnMap([
                [$checkout, [$lineItem1, $lineItem2], 'method1', 'type1', $organization1, Price::create(10.0, 'USD')],
                [$checkout, [$lineItem4], 'method3', 'type3', $organization3, null],
                [$checkout, [$lineItem5], 'method4', 'type4', null, Price::create(7.0, 'USD')]
            ]);

        $this->manager->updateLineItemGroupsShippingPrices($checkout);

        self::assertEquals(
            [
                'product.organization:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 10.0],
                'product.organization:3' => ['method' => 'method3', 'type' => 'type3'],
                'other-items'            => ['method' => 'method4', 'type' => 'type4', 'amount' => 7.0]
            ],
            $checkout->getLineItemGroupShippingData()
        );
    }
}
