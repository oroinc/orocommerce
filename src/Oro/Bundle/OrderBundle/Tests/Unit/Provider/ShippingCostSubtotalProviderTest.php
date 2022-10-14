<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\ShippingCostSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingCostSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var ShippingCostSubtotalProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($value) {
                return round($value, 2, PHP_ROUND_HALF_UP);
            });

        $this->provider = new ShippingCostSubtotalProvider(
            $this->translator,
            $roundingService,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testGetSubtotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.order.subtotals.' . ShippingCostSubtotalProvider::TYPE)
            ->willReturn(ucfirst(ShippingCostSubtotalProvider::TYPE));

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
}
