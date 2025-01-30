<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostCalculator;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Oro\Component\Testing\ReflectionUtil;

class MultiShippingCostProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var MultiShippingCostCalculator|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostCalculator;

    /** @var GroupLineItemHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupLineItemHelper;

    /** @var MultiShippingCostProvider */
    private $shippingCostProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->shippingCostCalculator = $this->createMock(MultiShippingCostCalculator::class);
        $this->groupLineItemHelper = $this->createMock(GroupLineItemHelperInterface::class);

        $this->shippingCostProvider = new MultiShippingCostProvider(
            $this->configProvider,
            $this->shippingCostCalculator,
            $this->groupLineItemHelper
        );
    }

    private function getCheckout(array $lineItems): Checkout
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection($lineItems));

        return $checkout;
    }

    private function getLineItem(?Organization $organization = null, ?Brand $brand = null): CheckoutLineItem
    {
        $product = new Product();
        $product->setOrganization($organization ?? $this->getOrganization(1));
        $product->setBrand($brand ?? $this->getBrand(1));

        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product);

        return $lineItem;
    }

    private function setLineItemShippingMethod(
        CheckoutLineItem $lineItem,
        string $shippingMethod,
        string $shippingMethodType
    ): void {
        $lineItem->setShippingMethod($shippingMethod);
        $lineItem->setShippingMethodType($shippingMethodType);
    }

    private function setLineItemShippingEstimateAmount(
        CheckoutLineItem $lineItem,
        float $amount,
        string $currency
    ): void {
        $lineItem->setShippingEstimateAmount($amount);
        $lineItem->setCurrency($currency);
    }

    private function getBrand(int $id): Brand
    {
        $brand = new Brand();
        ReflectionUtil::setId($brand, $id);

        return $brand;
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testGetCalculatedMultiShippingCostPerLineItemWhenLineItems(): void
    {
        $organization1 = $this->getOrganization(1);
        $lineItem1 = $this->getLineItem($organization1);
        $this->setLineItemShippingMethod($lineItem1, 'flat_rate_1', 'primary');
        $lineItem2 = $this->getLineItem($this->getOrganization(2));
        $this->setLineItemShippingMethod($lineItem2, 'flat_rate_2', 'primary');
        $this->setLineItemShippingEstimateAmount($lineItem2, 5.1, 'USD');
        $organization3 = $this->getOrganization(3);
        $lineItem3 = $this->getLineItem($organization3);
        $this->setLineItemShippingMethod($lineItem3, 'flat_rate_3', 'primary');
        $lineItem4 = $this->getLineItem($this->getOrganization(4));
        $checkout = $this->getCheckout([$lineItem1, $lineItem2, $lineItem3, $lineItem4]);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->shippingCostCalculator->expects(self::exactly(2))
            ->method('calculateShippingPrice')
            ->withConsecutive(
                [$checkout, [$lineItem1], 'flat_rate_1', 'primary', $organization1],
                [$checkout, [$lineItem3], 'flat_rate_3', 'primary', $organization3]
            )
            ->willReturnOnConsecutiveCalls(
                Price::create(1.2, 'USD'),
                null
            );

        self::assertSame(6.3, $this->shippingCostProvider->getCalculatedMultiShippingCost($checkout));
    }

    public function testGetCalculatedMultiShippingCostPerLineItemGroup(): void
    {
        $organization = $this->getOrganization(1);
        $brand1 = $this->getBrand(1);
        $lineItem1 = $this->getLineItem($organization, $brand1);
        $lineItem2 = $this->getLineItem($organization, $brand1);
        $lineItem3 = $this->getLineItem($organization, $this->getBrand(2));
        $lineItem4 = $this->getLineItem($organization, $this->getBrand(3));
        $lineItem5 = new CheckoutLineItem();
        $lineItem5->setProductSku('FREE_FORM_PRODUCT');
        $lineItem6 = $this->getLineItem($organization, $this->getBrand(4));
        $checkout = $this->getCheckout([$lineItem1, $lineItem2, $lineItem3, $lineItem4, $lineItem5, $lineItem6]);
        $checkout->setLineItemGroupShippingData([
            'product.brand:1' => ['method' => 'flat_rate_1', 'type' => 'primary'],
            'product.brand:2' => ['method' => 'flat_rate_2', 'type' => 'primary', 'amount' => 5.1],
            'product.brand:3' => ['method' => 'flat_rate_3', 'type' => 'primary'],
            'other-items'     => ['method' => 'flat_rate_4', 'type' => 'primary']
        ]);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.brand');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.brand')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::never())
            ->method('getGroupingFieldValue');
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($checkout->getLineItems(), 'product.brand')
            ->willReturn([
                'product.brand:1' => [$lineItem1, $lineItem2],
                'product.brand:2' => [$lineItem3],
                'product.brand:3' => [$lineItem4],
                'product.brand:4' => [$lineItem6],
                'other-items'     => [$lineItem5]
            ]);

        $this->shippingCostCalculator->expects(self::exactly(3))
            ->method('calculateShippingPrice')
            ->withConsecutive(
                [$checkout, [$lineItem1, $lineItem2], 'flat_rate_1', 'primary', null],
                [$checkout, [$lineItem4], 'flat_rate_3', 'primary', null],
                [$checkout, [$lineItem5], 'flat_rate_4', 'primary', null]
            )
            ->willReturnOnConsecutiveCalls(
                Price::create(1.2, 'USD'),
                null,
                Price::create(1.3, 'USD')
            );

        self::assertSame(7.6, $this->shippingCostProvider->getCalculatedMultiShippingCost($checkout));
    }

    public function testGetCalculatedMultiShippingCostPerLineItemGroupWhenLineItemsAreGroupedByOrg(): void
    {
        $organization1 = $this->getOrganization(1);
        $lineItem1 = $this->getLineItem($organization1);
        $lineItem2 = $this->getLineItem($organization1);
        $lineItem3 = $this->getLineItem($this->getOrganization(2));
        $organization3 = $this->getOrganization(3);
        $lineItem4 = $this->getLineItem($organization3);
        $lineItem5 = new CheckoutLineItem();
        $lineItem5->setProductSku('FREE_FORM_PRODUCT');
        $lineItem6 = $this->getLineItem($this->getOrganization(4));
        $checkout = $this->getCheckout([$lineItem1, $lineItem2, $lineItem3, $lineItem4, $lineItem5, $lineItem6]);
        $checkout->setLineItemGroupShippingData([
            'product.organization:1' => ['method' => 'flat_rate_1', 'type' => 'primary'],
            'product.organization:2' => ['method' => 'flat_rate_2', 'type' => 'primary', 'amount' => 5.1],
            'product.organization:3' => ['method' => 'flat_rate_3', 'type' => 'primary'],
            'other-items'            => ['method' => 'flat_rate_4', 'type' => 'primary']
        ]);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupingFieldPath')
            ->willReturn('product.organization');
        $this->groupLineItemHelper->expects(self::once())
            ->method('isLineItemsGroupedByOrganization')
            ->with('product.organization')
            ->willReturn(true);
        $this->groupLineItemHelper->expects(self::exactly(2))
            ->method('getGroupingFieldValue')
            ->willReturnCallback(function (CheckoutLineItem $lineItem) {
                return $lineItem->getProduct()->getOrganization();
            });
        $this->groupLineItemHelper->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($checkout->getLineItems(), 'product.organization')
            ->willReturn([
                'product.organization:1' => [$lineItem1, $lineItem2],
                'product.organization:2' => [$lineItem3],
                'product.organization:3' => [$lineItem4],
                'product.organization:4' => [$lineItem6],
                'other-items'            => [$lineItem5]
            ]);

        $this->shippingCostCalculator->expects(self::exactly(3))
            ->method('calculateShippingPrice')
            ->withConsecutive(
                [$checkout, [$lineItem1, $lineItem2], 'flat_rate_1', 'primary', $organization1],
                [$checkout, [$lineItem4], 'flat_rate_3', 'primary', $organization3],
                [$checkout, [$lineItem5], 'flat_rate_4', 'primary', null]
            )
            ->willReturnOnConsecutiveCalls(
                Price::create(1.2, 'USD'),
                null,
                Price::create(1.3, 'USD')
            );

        self::assertSame(7.6, $this->shippingCostProvider->getCalculatedMultiShippingCost($checkout));
    }
}
