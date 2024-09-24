<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutSubOrderShippingPriceProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;

class CheckoutSubOrderShippingPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var CheckoutSubOrderShippingPriceProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);

        $this->provider = new CheckoutSubOrderShippingPriceProvider(
            $this->checkoutShippingMethodsProvider,
            $this->configProvider,
            $this->organizationProvider
        );
    }

    public function testGetPriceWhenNoOrganization(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $price = Price::create(1.0, 'USD');

        $this->configProvider->expects(self::never())
            ->method(self::anything());

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->checkoutShippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn($price);

        self::assertSame($price, $this->provider->getPrice($checkout));
    }

    public function testGetPriceWhenPriceIsNull(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects(self::never())
            ->method(self::anything());

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->checkoutShippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);

        self::assertNull($this->provider->getPrice($checkout));
    }

    public function testGetPriceWhenShippingSelectionByLineItemEnabled(): void
    {
        $organization = $this->createMock(Organization::class);
        $checkout = $this->createMock(Checkout::class);
        $price = Price::create(1.0, 'USD');

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->checkoutShippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn($price);

        self::assertSame($price, $this->provider->getPrice($checkout, $organization));
    }

    public function testGetPriceWhenShippingSelectionByLineItemDisabled(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $checkout = $this->createMock(Checkout::class);
        $price = Price::create(1.0, 'USD');

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->checkoutShippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn($price);

        self::assertSame($price, $this->provider->getPrice($checkout, $organization));
    }
}
