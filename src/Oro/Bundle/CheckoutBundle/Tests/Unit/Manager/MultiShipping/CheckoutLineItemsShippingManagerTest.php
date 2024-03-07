<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Manager\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostCalculator;

class CheckoutLineItemsShippingManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LineItemShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemShippingMethodsProvider;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsProvider;

    /** @var MultiShippingCostCalculator|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostCalculator;

    /** @var GroupLineItemHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupLineItemHelper;

    /** @var CheckoutLineItemsShippingManager */
    private $manager;

    protected function setUp(): void
    {
        $this->lineItemShippingMethodsProvider = $this->createMock(LineItemShippingMethodsProviderInterface::class);
        $this->lineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->shippingCostCalculator = $this->createMock(MultiShippingCostCalculator::class);
        $this->groupLineItemHelper = $this->createMock(GroupLineItemHelperInterface::class);

        $this->manager = new CheckoutLineItemsShippingManager(
            $this->lineItemShippingMethodsProvider,
            $this->lineItemsProvider,
            $this->shippingCostCalculator,
            $this->groupLineItemHelper
        );
    }

    private function getLineItem(
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
        $lineItem1 = $this->getLineItem('sku-1', 'item');
        $lineItem2 = $this->getLineItem('sku-2', 'set');
        $lineItem21 = $this->getLineItem('sku-2', 'set', 'sample_checksum');
        $lineItem3 = $this->getLineItem('sku-3', 'item');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem21);
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem21, $lineItem3]));

        $shippingData = [
            'sku-1:item:'               => ['method' => 'method1', 'type' => 'type1'],
            'sku-2:set:'                => ['method' => 'method2', 'type' => 'type2'],
            'sku-2:set:sample_checksum' => ['method' => 'method1', 'type' => 'type1'],
            'sku-4:item:'               => ['method' => 'method1', 'type' => 'type1']
        ];

        $this->manager->updateLineItemsShippingMethods($shippingData, $checkout);

        self::assertEquals('method1', $lineItem1->getShippingMethod());
        self::assertEquals('type1', $lineItem1->getShippingMethodType());

        self::assertEquals('method2', $lineItem2->getShippingMethod());
        self::assertEquals('type2', $lineItem2->getShippingMethodType());

        self::assertEquals('method1', $lineItem21->getShippingMethod());
        self::assertEquals('type1', $lineItem21->getShippingMethodType());

        self::assertEmpty($lineItem3->getShippingMethod());
        self::assertEmpty($lineItem3->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWhenShippingDataIsNull(): void
    {
        $lineItem1 = $this->getLineItem('sku-1', 'item');
        $lineItem2 = $this->getLineItem('sku-2', 'set');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->manager->updateLineItemsShippingMethods(null, $checkout);

        self::assertEmpty($lineItem1->getShippingMethod());
        self::assertEmpty($lineItem1->getShippingMethodType());

        self::assertEmpty($lineItem2->getShippingMethod());
        self::assertEmpty($lineItem2->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWithDefaultsExists(): void
    {
        $lineItem1 = $this->getLineItem('sku-1', 'item');
        $lineItem3 = $this->getLineItem('sku-3', 'item');
        $lineItem31 = $this->getLineItem('sku-3', 'item', 'sample_checksum');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem3);
        $checkout->addLineItem($lineItem31);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem3, $lineItem31]));

        $this->lineItemShippingMethodsProvider->expects(self::exactly(2))
            ->method('getAvailableShippingMethods')
            ->withConsecutive([$lineItem3], [$lineItem31])
            ->willReturn([
                'method1' => ['identifier' => 'method1', 'types' => ['primary' => ['identifier' => 'primary']]],
                'method2' => ['identifier' => 'method2', 'types' => ['primary' => ['identifier' => 'primary_2']]]
            ]);

        $shippingData = [
            'sku-1:item:' => ['method' => 'method1', 'type' => 'type1'],
            'sku-4:item:' => ['method' => 'method1', 'type' => 'type1']
        ];

        $this->manager->updateLineItemsShippingMethods($shippingData, $checkout, true);

        self::assertEquals('method1', $lineItem1->getShippingMethod());
        self::assertEquals('type1', $lineItem1->getShippingMethodType());

        self::assertEquals('method1', $lineItem3->getShippingMethod());
        self::assertEquals('primary', $lineItem3->getShippingMethodType());

        self::assertEquals('method1', $lineItem31->getShippingMethod());
        self::assertEquals('primary', $lineItem31->getShippingMethodType());
    }

    public function testUpdateLineItemsShippingMethodsWithEmptyDefaults(): void
    {
        $lineItem1 = $this->getLineItem('sku-1', 'item');
        $lineItem11 = $this->getLineItem('sku-1', 'item', 'sample_checksum');
        $lineItem3 = $this->getLineItem('sku-3', 'item');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem11);
        $checkout->addLineItem($lineItem3);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem11, $lineItem3]));

        $this->lineItemShippingMethodsProvider->expects(self::once())
            ->method('getAvailableShippingMethods')
            ->with($lineItem3)
            ->willReturn([]);

        $shippingData = [
            'sku-1:item:'                => ['method' => 'method1', 'type' => 'type1'],
            'sku-1:item:sample_checksum' => ['method' => 'method2', 'type' => 'type2'],
            'sku-4:item:'                => ['method' => 'method1', 'type' => 'type1']
        ];

        $this->manager->updateLineItemsShippingMethods($shippingData, $checkout, true);

        self::assertEquals('method1', $lineItem1->getShippingMethod());
        self::assertEquals('type1', $lineItem1->getShippingMethodType());

        self::assertEquals('method2', $lineItem11->getShippingMethod());
        self::assertEquals('type2', $lineItem11->getShippingMethodType());

        self::assertEmpty($lineItem3->getShippingMethod());
        self::assertEmpty($lineItem3->getShippingMethodType());
    }

    public function testGetCheckoutLineItemsShippingData(): void
    {
        $lineItem1 = $this->getLineItem('sku-1', 'item', '', 'method1', 'type1');
        $lineItem2 = $this->getLineItem('sku-2', 'set', '', 'method2', 'type2');
        $lineItem21 = $this->getLineItem('sku-2', 'set', 'sample_checksum', 'method21', 'type21');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem21);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem21]));

        $expected = [
            'sku-1:item:'               => ['method' => 'method1', 'type' => 'type1'],
            'sku-2:set:'                => ['method' => 'method2', 'type' => 'type2'],
            'sku-2:set:sample_checksum' => ['method' => 'method21', 'type' => 'type21']
        ];

        $result = $this->manager->getCheckoutLineItemsShippingData($checkout);
        self::assertEquals($expected, $result);
    }

    public function testUpdateLineItemsShippingPrices(): void
    {
        $lineItem1 = $this->getLineItem('sku-1', 'item', '', 'method1', 'type1');
        $lineItem2 = $this->getLineItem('sku-2', 'set', '', 'method1', 'type1');
        $lineItem3 = $this->getLineItem('sku-3', 'item');
        $lineItem4 = $this->getLineItem('sku-4', 'set', '', 'method1', 'type1');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem3);
        $checkout->addLineItem($lineItem4);

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.brand');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.brand')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3, $lineItem4]));

        $this->shippingCostCalculator->expects(self::exactly(3))
            ->method('calculateShippingPrice')
            ->willReturnMap([
                [$checkout, [$lineItem1], 'method1', 'type1', null, Price::create(10.0, 'USD')],
                [$checkout, [$lineItem2], 'method1', 'type1', null, Price::create(7.0, 'EUR')],
                [$checkout, [$lineItem4], 'method1', 'type1', null, null]
            ]);

        $this->manager->updateLineItemsShippingPrices($checkout);

        self::assertEquals(10.0, $lineItem1->getShippingEstimateAmount());
        self::assertEquals('USD', $lineItem1->getCurrency());

        self::assertEquals(7.0, $lineItem2->getShippingEstimateAmount());
        self::assertEquals('EUR', $lineItem2->getCurrency());

        self::assertNull($lineItem3->getShippingEstimateAmount());
        self::assertNull($lineItem3->getCurrency());

        self::assertNull($lineItem4->getShippingEstimateAmount());
        self::assertNull($lineItem4->getCurrency());
    }

    public function testUpdateLineItemsShippingPricesWhenLineItemsAreGroupedByOrganization(): void
    {
        $organization = $this->createMock(Organization::class);

        $lineItem1 = $this->getLineItem('sku-1', 'item', '', 'method1', 'type1');
        $lineItem2 = $this->getLineItem('sku-2', 'set', '', 'method1', 'type1');
        $lineItem3 = $this->getLineItem('sku-3', 'item');
        $lineItem4 = $this->getLineItem('sku-4', 'set', '', 'method1', 'type1');

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem3);
        $checkout->addLineItem($lineItem4);

        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.organization');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.organization')
            ->willReturn(true);
        $this->groupLineItemHelper->expects(self::exactly(3))
            ->method('getGroupingFieldValue')
            ->with(self::isInstanceOf(CheckoutLineItem::class), 'product.organization')
            ->willReturn($organization);

        $this->lineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3, $lineItem4]));

        $this->shippingCostCalculator->expects(self::exactly(3))
            ->method('calculateShippingPrice')
            ->willReturnMap([
                [$checkout, [$lineItem1], 'method1', 'type1', $organization, Price::create(10.0, 'USD')],
                [$checkout, [$lineItem2], 'method1', 'type1', $organization, Price::create(7.0, 'EUR')],
                [$checkout, [$lineItem4], 'method1', 'type1', $organization, null]
            ]);

        $this->manager->updateLineItemsShippingPrices($checkout);

        self::assertEquals(10.0, $lineItem1->getShippingEstimateAmount());
        self::assertEquals('USD', $lineItem1->getCurrency());

        self::assertEquals(7.0, $lineItem2->getShippingEstimateAmount());
        self::assertEquals('EUR', $lineItem2->getCurrency());

        self::assertNull($lineItem3->getShippingEstimateAmount());
        self::assertNull($lineItem3->getCurrency());

        self::assertNull($lineItem4->getShippingEstimateAmount());
        self::assertNull($lineItem4->getCurrency());
    }

    public function testGetLineItemIdentifier(): void
    {
        $lineItem = $this->getLineItem('sku-1', 'item');
        $key = $this->manager->getLineItemIdentifier($lineItem);

        self::assertEquals('sku-1:item:', $key);
    }

    public function testGetLineItemIdentifierWithChecksum(): void
    {
        $lineItem = $this->getLineItem('sku-1', 'item', 'sample_checksum');
        $key = $this->manager->getLineItemIdentifier($lineItem);

        self::assertEquals('sku-1:item:sample_checksum', $key);
    }
}
