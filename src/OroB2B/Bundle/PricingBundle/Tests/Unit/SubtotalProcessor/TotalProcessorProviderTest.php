<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityWithoutCurrencyStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemStub;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class TotalProcessorProviderTest extends \PHPUnit_Framework_TestCase
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
            $this->getMock('OroB2B\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry');

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
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(LineItemSubtotalProvider::TYPE);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testGetTotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $total = $this->provider->getTotal($entity);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertInternalType('float', $total->getAmount());
        $this->assertEquals(142.0, $total->getAmount());
    }

    public function testSubtotalsCache()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(LineItemSubtotalProvider::TYPE);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);

        // try to get again but getProviders and getSubtotal expect run once
        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(LineItemSubtotalProvider::TYPE);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testClearSubtotalsCache()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub(), 2);

        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(LineItemSubtotalProvider::TYPE);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->provider->clearCache();

        // try to get again and getProviders and getSubtotal expect run twice
        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(LineItemSubtotalProvider::TYPE);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testGetName()
    {
        $this->assertEquals(TotalProcessorProvider::NAME, $this->provider->getName());
    }

    /**
     * @param EntityStub $entity
     * @param int $runCount
     *
     * @return mixed
     */
    protected function prepareSubtotals($entity, $runCount = 1)
    {
        $currency = 'USD';
        $subtotalProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $perUnitLineItem = new LineItemStub();
        $perUnitLineItem->setPriceType(LineItemStub::PRICE_TYPE_UNIT);
        $perUnitLineItem->setPrice(Price::create(20, 'USD'));
        $perUnitLineItem->setQuantity(2);

        $bundledUnitLineItem = new LineItemStub();
        $bundledUnitLineItem->setPriceType(LineItemStub::PRICE_TYPE_BUNDLED);
        $bundledUnitLineItem->setPrice(Price::create(2, 'USD'));
        $bundledUnitLineItem->setQuantity(10);

        $otherCurrencyLineItem = new LineItemStub();
        $otherCurrencyLineItem->setPriceType(LineItemStub::PRICE_TYPE_UNIT);
        $otherCurrencyLineItem->setPrice(Price::create(10, 'EUR'));
        $otherCurrencyLineItem->setQuantity(10);

        $emptyLineItem = new LineItemStub();

        $entity->addLineItem($perUnitLineItem);
        $entity->addLineItem($bundledUnitLineItem);
        $entity->addLineItem($emptyLineItem);
        $entity->addLineItem($otherCurrencyLineItem);

        if ($entity instanceof CurrencyAwareInterface) {
            $entity->setCurrency($currency);
        }

        $sub = new Subtotal();
        $sub->setCurrency($currency);
        $sub->setAmount(142.0);
        $sub->setType(LineItemSubtotalProvider::TYPE);
        $sub->setLabel('Total');

        $this->subtotalProviderRegistry
            ->expects($this->exactly($runCount))
            ->method('getSupportedProviders')
            ->willReturn([$subtotalProvider]);
        $subtotalProvider
            ->expects($this->exactly($runCount))
            ->method('getSubtotal')
            ->willReturn($sub);

        return $entity;
    }

    public function testGetTotalInDefaultCurrency()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityWithoutCurrencyStub());

        $total = $this->provider->getTotal($entity);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals('USD', $total->getCurrency());
        $this->assertInternalType('float', $total->getAmount());
        $this->assertEquals(142.0, $total->getAmount());
    }
}
