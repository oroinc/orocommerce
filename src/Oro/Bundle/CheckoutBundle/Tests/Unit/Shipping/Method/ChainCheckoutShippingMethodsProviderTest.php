<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\ChainCheckoutShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

class ChainCheckoutShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var ChainCheckoutShippingMethodsProvider */
    private $chainProvider;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->provider2 = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $this->chainProvider = new ChainCheckoutShippingMethodsProvider([$this->provider1, $this->provider2]);
    }

    public function testGetApplicableMethodsViewsWhenViewsReturnedByFirstProvider(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $views = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', [])
            ->addMethodTypeView('flat_rate', 'flat_rate_1', []);

        $this->provider1->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkout))
            ->willReturn($views);
        $this->provider2->expects(self::never())
            ->method('getApplicableMethodsViews');

        self::assertSame($views, $this->chainProvider->getApplicableMethodsViews($checkout));
    }

    public function testGetApplicableMethodsViewsWhenViewsReturnedBySecondProvider(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $views = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', [])
            ->addMethodTypeView('flat_rate', 'flat_rate_1', []);

        $this->provider1->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkout))
            ->willReturn(new ShippingMethodViewCollection());
        $this->provider2->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkout))
            ->willReturn($views);

        self::assertSame($views, $this->chainProvider->getApplicableMethodsViews($checkout));
    }

    public function testGetApplicableMethodsViewsWhenViewsDoNotReturnedByAnyProvider(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->provider1->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkout))
            ->willReturn(new ShippingMethodViewCollection());
        $this->provider2->expects(self::once())
            ->method('getApplicableMethodsViews')
            ->with(self::identicalTo($checkout))
            ->willReturn(new ShippingMethodViewCollection());

        self::assertEquals(
            new ShippingMethodViewCollection(),
            $this->chainProvider->getApplicableMethodsViews($checkout)
        );
    }

    public function testGetPriceWhenPriceReturnedByFirstProvider(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $price = $this->createMock(Price::class);

        $this->provider1->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn($price);
        $this->provider2->expects(self::never())
            ->method('getPrice');

        self::assertSame($price, $this->chainProvider->getPrice($checkout));
    }

    public function testGetPriceWhenPriceReturnedBySecondProvider(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $price = $this->createMock(Price::class);

        $this->provider1->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);
        $this->provider2->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn($price);

        self::assertSame($price, $this->chainProvider->getPrice($checkout));
    }

    public function testGetPriceWhenPriceDoesNotReturnedByAnyProvider(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->provider1->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);
        $this->provider2->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);

        self::assertNull($this->chainProvider->getPrice($checkout));
    }
}
