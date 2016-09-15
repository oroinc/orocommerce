<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\ShippingCostSubtotalProvider;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;

class ShippingCostSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    /**
     * @var ShippingCostSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RoundingServiceInterface
     */
    protected $roundingService;

    protected function setUp()
    {
        parent::setUp();
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->roundingService = $this->getMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 2, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->provider = new ShippingCostSubtotalProvider(
            $this->translator,
            $this->roundingService,
            $this->currencyManager
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider);
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
        $order->setShippingCost(Price::create($costAmount, $order->getCurrency()));

        $subtotal = $this->provider->getSubtotal($order);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(ShippingCostSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(ShippingCostSubtotalProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals($costAmount, $subtotal->getAmount());
    }
}
