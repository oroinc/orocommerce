<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalLineItemProvider;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class SubtotalLineItemProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubtotalLineItemProvider
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
                        return round($value, 0, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->provider = new SubtotalLineItemProvider($this->translator, $this->roundingService);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider);
    }

    public function testGetSubtotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.order.subtotals.%s', SubtotalLineItemProvider::TYPE))
            ->willReturn(ucfirst(SubtotalLineItemProvider::TYPE));

        $order = new Order();
        $perUnitLineItem = new OrderLineItem();
        $perUnitLineItem->setPriceType(OrderLineItem::PRICE_TYPE_UNIT);
        $perUnitLineItem->setPrice(Price::create(20, 'USD'));
        $perUnitLineItem->setQuantity(2);

        $bundledUnitLineItem = new OrderLineItem();
        $bundledUnitLineItem->setPriceType(OrderLineItem::PRICE_TYPE_BUNDLED);
        $bundledUnitLineItem->setPrice(Price::create(2, 'USD'));
        $bundledUnitLineItem->setQuantity(10);

        $otherCurrencyLineItem = new OrderLineItem();
        $otherCurrencyLineItem->setPriceType(OrderLineItem::PRICE_TYPE_UNIT);
        $otherCurrencyLineItem->setPrice(Price::create(10, 'EUR'));
        $otherCurrencyLineItem->setQuantity(10);

        $emptyLineItem = new OrderLineItem();

        $order->addLineItem($perUnitLineItem);
        $order->addLineItem($bundledUnitLineItem);
        $order->addLineItem($emptyLineItem);
        $order->addLineItem($otherCurrencyLineItem);

        $order->setCurrency('USD');

        $subtotal = $this->provider->getSubtotal($order);
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalLineItemProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(SubtotalLineItemProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }
}
