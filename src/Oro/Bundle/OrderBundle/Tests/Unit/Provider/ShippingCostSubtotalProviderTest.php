<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\ShippingCostSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingCostSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    private ShippingCostSubtotalProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('oro.order.subtotals.' . ShippingCostSubtotalProvider::TYPE)
            ->willReturn(ucfirst(ShippingCostSubtotalProvider::TYPE));

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($value) {
                return round($value, 2, PHP_ROUND_HALF_UP);
            });

        $this->provider = new ShippingCostSubtotalProvider(
            $translator,
            $roundingService,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testGetSubtotal(): void
    {
        $order = new Order();
        $currency = 'USD';
        $costAmount = 142.12;
        $order->setCurrency($currency);
        $order->setEstimatedShippingCostAmount($costAmount);

        $subtotal = $this->provider->getSubtotal($order);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(ShippingCostSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(ShippingCostSubtotalProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals(200, $subtotal->getSortOrder());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($costAmount, $subtotal->getAmount());
    }

    public function testGetSubtotalDemandQuote(): void
    {
        $costAmount = 143.55;
        $currency = 'EUR';
        $quoteDemand = new QuoteDemand();
        $quote = new Quote();
        $quote->setEstimatedShippingCostAmount(143.55);
        $quote->setCurrency($currency);
        $quoteDemand->setQuote($quote);

        $subtotal = $this->provider->getSubtotal($quoteDemand);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(ShippingCostSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(ShippingCostSubtotalProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($currency, $subtotal->getCurrency());
        $this->assertEquals(200, $subtotal->getSortOrder());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($costAmount, $subtotal->getAmount());
    }
}
