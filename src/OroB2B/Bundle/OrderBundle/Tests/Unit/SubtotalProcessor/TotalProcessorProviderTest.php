<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\SubtotalProcessor;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalLineItemProvider;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\OrderBundle\SubtotalProcessor\SubtotalProviderRegistry;
use OroB2B\Bundle\OrderBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalProviderProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SubtotalProviderRegistry
     */
    protected $subtotalProviderRegistry;

    /**
     * @var TotalProcessorProvider
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
        $this->subtotalProviderRegistry =
            $this->getMock('OroB2B\Bundle\OrderBundle\SubtotalProcessor\SubtotalProviderRegistry');

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

        $this->provider = new TotalProcessorProvider(
            $this->subtotalProviderRegistry,
            $this->translator,
            $this->roundingService
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->provider);
    }

    public function testGetSubtotals()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('orob2b.order.subtotals.%s', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $order = $this->prepareSubtotals();

        $subtotals = $this->provider->getSubtotals($order);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(SubtotalLineItemProvider::TYPE);

        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalLineItemProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testGetTotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.order.subtotals.%s', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $order = $this->prepareSubtotals();

        $total = $this->provider->getTotal($order);
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($order->getCurrency(), $total->getCurrency());
        $this->assertInternalType('float', $total->getAmount());
        $this->assertEquals(142.0, $total->getAmount());
    }

    public function testSubtotalsCache()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('orob2b.order.subtotals.%s', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $order = $this->prepareSubtotals();

        $subtotals = $this->provider->getSubtotals($order);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(SubtotalLineItemProvider::TYPE);
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);

        // try to get again but getProviders and getSubtotal expect run once
        $subtotals = $this->provider->getSubtotals($order);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(SubtotalLineItemProvider::TYPE);

        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalLineItemProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testClearSubtotalsCache()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('orob2b.order.subtotals.%s', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $order = $this->prepareSubtotals(2);

        $subtotals = $this->provider->getSubtotals($order);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(SubtotalLineItemProvider::TYPE);
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->provider->clearCache();

        // try to get again and getProviders and getSubtotal expect run twice
        $subtotals = $this->provider->getSubtotals($order);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(SubtotalLineItemProvider::TYPE);

        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(SubtotalLineItemProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    /**
     * @param int $runCount
     *
     * @return Order
     */
    protected function prepareSubtotals($runCount = 1)
    {
        $subtotalProvider = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\SubtotalLineItemProvider')
            ->disableOriginalConstructor()
            ->getMock();

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

        $sub = new Subtotal();
        $sub->setCurrency($order->getCurrency());
        $sub->setAmount(142.0);
        $sub->setType(SubtotalLineItemProvider::TYPE);
        $sub->setLabel('Total');

        $this->subtotalProviderRegistry
            ->expects($this->exactly($runCount))
            ->method('getProviders')
            ->willReturn([$subtotalProvider]);
        $subtotalProvider
            ->expects($this->exactly($runCount))
            ->method('getSubtotal')
            ->willReturn($sub);

        return $order;
    }
}
