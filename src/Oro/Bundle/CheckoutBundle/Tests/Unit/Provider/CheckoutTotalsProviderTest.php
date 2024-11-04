<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class CheckoutTotalsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutToOrderConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutToOrderConverter;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalsProvider;

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    /** @var CheckoutTotalsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutToOrderConverter = $this->createMock(CheckoutToOrderConverter::class);
        $this->totalsProvider = $this->createMock(TotalProcessorProvider::class);
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $this->provider = new CheckoutTotalsProvider(
            $this->checkoutToOrderConverter,
            $this->totalsProvider,
            $this->checkoutShippingMethodsProvider
        );
    }

    public function testGetTotalsArray()
    {
        $checkout = new Checkout();
        $order = new Order();
        $shippingCost = Price::create(10, 'USD');

        $this->checkoutShippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with(self::identicalTo($checkout))
            ->willReturn($shippingCost);

        $this->checkoutToOrderConverter->expects(self::once())
            ->method('getOrder')
            ->with(self::identicalTo($checkout))
            ->willReturnCallback(function (Checkout $checkout) use ($order, $shippingCost) {
                self::assertSame($shippingCost, $checkout->getShippingCost());

                return $order;
            });

        $this->totalsProvider->expects(self::once())
            ->method('enableRecalculation');

        $totals = [
            'total' => [
                'type' => 'total',
                'label' => 'Total',
                'amount' => 10,
                'currency' => 'USD'
            ]
        ];
        $this->totalsProvider->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with(self::identicalTo($order))
            ->willReturn($totals);

        $this->assertSame($totals, $this->provider->getTotalsArray($checkout));
    }
}
