<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostCalculator;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class MultiShippingCostCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var MultiShippingCostCalculator */
    private $shippingCostCalculator;

    protected function setUp(): void
    {
        $this->shippingPriceProvider = $this->createMock(ShippingPriceProviderInterface::class);
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);

        $this->shippingCostCalculator = new MultiShippingCostCalculator(
            $this->shippingPriceProvider,
            $this->checkoutShippingContextProvider,
            $this->checkoutFactory,
            $this->organizationProvider
        );
    }

    public function testCalculateShippingPrice(): void
    {
        $checkout = new Checkout();
        $lineItem = new CheckoutLineItem();
        $shippingMethod = 'method1';
        $shippingMethodType = 'type1';

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects(self::once())
            ->method('getContext')
            ->willReturn($shippingContext);

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($shippingContext), $shippingMethod, $shippingMethodType)
            ->willReturn(Price::create(10.0, 'USD'));

        $price = $this->shippingCostCalculator->calculateShippingPrice(
            $checkout,
            [$lineItem],
            $shippingMethod,
            $shippingMethodType
        );

        self::assertEquals(10.0, $price->getValue());
        self::assertEquals('USD', $price->getCurrency());
    }

    public function testCalculateShippingPriceForSpecificOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $checkout = new Checkout();
        $lineItem = new CheckoutLineItem();
        $shippingMethod = 'method1';
        $shippingMethodType = 'type1';

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects(self::once())
            ->method('getContext')
            ->willReturn($shippingContext);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([self::identicalTo($organization)], [self::identicalTo($previousOrganization)]);

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($shippingContext), $shippingMethod, $shippingMethodType)
            ->willReturn(Price::create(10.0, 'USD'));

        $price = $this->shippingCostCalculator->calculateShippingPrice(
            $checkout,
            [$lineItem],
            $shippingMethod,
            $shippingMethodType,
            $organization
        );

        self::assertEquals(10.0, $price->getValue());
        self::assertEquals('USD', $price->getCurrency());
    }

    public function testCalculateShippingPriceWhenPriceIsNull(): void
    {
        $checkout = new Checkout();
        $lineItem = new CheckoutLineItem();
        $shippingMethod = 'method1';
        $shippingMethodType = 'type1';

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->checkoutShippingContextProvider->expects(self::once())
            ->method('getContext')
            ->willReturn($shippingContext);

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingPriceProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($shippingContext), $shippingMethod, $shippingMethodType)
            ->willReturn(null);

        $price = $this->shippingCostCalculator->calculateShippingPrice(
            $checkout,
            [$lineItem],
            $shippingMethod,
            $shippingMethodType
        );

        self::assertNull($price);
    }
}
