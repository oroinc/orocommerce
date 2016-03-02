<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalShippingCostProvider;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class SubtotalShippingCostProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubtotalShippingCostProvider
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
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->roundingService = $this->getMock('OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface');
        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 2, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->provider = new SubtotalShippingCostProvider($this->translator, $this->roundingService);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider);
    }

    public function testGetSubtotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.order.subtotals.' . SubtotalShippingCostProvider::TYPE)
            ->willReturn(ucfirst(SubtotalShippingCostProvider::TYPE));

        $order = new Order();
        $currency = 'USD';
        $costAmount = 142.12;
        $order->setCurrency($currency);
        $order->setShippingCost(Price::create($costAmount, $order->getCurrency()));

        $subtotal = $this->provider->getSubtotal($order);
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalShippingCostProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(SubtotalShippingCostProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals($costAmount, $subtotal->getAmount());
    }
}
