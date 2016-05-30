<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityWithoutCurrencyStub;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class TotalProcessorProviderTest extends AbstractSubtotalProviderTest
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
        parent::setUp();
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
            $this->roundingService,
            $this->currencyManager
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
        $subtotal = $subtotals->get(0);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertInternalType('float', $subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetSubtotalsException()
    {
        $this->provider->getSubtotals(null);
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
        $this->assertEquals(182.0, $total->getAmount());
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
        $subtotal = $subtotals->get(0);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);

        // try to get again but getProviders and getSubtotal expect run once
        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(0);

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
        $subtotal1 = $subtotals->get(0);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal1);
        $this->provider->clearCache();

        // try to get again and getProviders and getSubtotal expect run twice
        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal1 = $subtotals->get(0);
        $subtotal2 = $subtotals->get(1);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal1);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal1->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal1->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal1->getCurrency());
        $this->assertInternalType('float', $subtotal1->getAmount());
        $this->assertEquals(142.0, $subtotal1->getAmount());
        $this->assertEquals(40.0, $subtotal2->getAmount());
    }

    public function testGetName()
    {
        $this->assertEquals(TotalProcessorProvider::NAME, $this->provider->getName());
    }

    //todo: Fix
    public function testGetTotalInDefaultCurrency()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = new EntityWithoutCurrencyStub();
        $this->currencyManager->expects($this->once())
            ->method('getBaseCurrency')
            ->with($entity)
            ->willReturn('USD');

        $entity = $this->prepareSubtotals($entity);

        $total = $this->provider->getTotal($entity);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals('USD', $total->getCurrency());
        $this->assertInternalType('float', $total->getAmount());
        $this->assertEquals(182.0, $total->getAmount());
    }

    public function testGetTotalSubstractOperation()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub(), 1, Subtotal::OPERATION_SUBTRACTION);

        $total = $this->provider->getTotal($entity);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertInternalType('float', $total->getAmount());
        $this->assertEquals(102.0, $total->getAmount());
    }

    public function testGetTotalSubstractOperationMinLimit()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub(), 1, Subtotal::OPERATION_SUBTRACTION, 200.0);

        $total = $this->provider->getTotal($entity);
        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertInternalType('float', $total->getAmount());
        $this->assertEquals(0.0, $total->getAmount());
    }

    /**
     * @param EntityStub|EntityWithoutCurrencyStub $entity
     * @param int $runCount
     * @param int $operation
     * @param float $subtotalAmount
     *
     * @return EntityStub|EntityWithoutCurrencyStub
     */
    protected function prepareSubtotals(
        $entity,
        $runCount = 1,
        $operation = Subtotal::OPERATION_ADD,
        $subtotalAmount = 40.0
    ) {
        $currency = 'USD';
        $subtotalProvider1 = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $subtotalProvider2 = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        if ($entity instanceof CurrencyAwareInterface) {
            $entity->setCurrency($currency);
        }

        $subtotal1 = new Subtotal();
        $subtotal1->setCurrency($currency);
        $subtotal1->setAmount(142.0);
        $subtotal1->setType(LineItemSubtotalProvider::TYPE);
        $subtotal1->setLabel('Total');
        $subtotal1->setOperation(Subtotal::OPERATION_ADD);

        $subtotal2 = new Subtotal();
        $subtotal2->setCurrency($currency);
        $subtotal2->setAmount($subtotalAmount);
        $subtotal2->setType(LineItemSubtotalProvider::TYPE);
        $subtotal2->setLabel('Total');
        $subtotal2->setOperation($operation);

        $this->subtotalProviderRegistry
            ->expects($this->exactly($runCount))
            ->method('getSupportedProviders')
            ->willReturn([$subtotalProvider1, $subtotalProvider2]);
        $subtotalProvider1
            ->expects($this->exactly($runCount))
            ->method('getSubtotal')
            ->willReturn($subtotal1);
        $subtotalProvider2
            ->expects($this->exactly($runCount))
            ->method('getSubtotal')
            ->willReturn($subtotal2);

        return $entity;
    }


    public function testGetTotalWithSubtotalsAsArray()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('orob2b.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $totals = $this->provider->getTotalWithSubtotalsAsArray($entity);
        $this->assertInternalType('array', $totals);
        $this->assertArrayHasKey(TotalProcessorProvider::TYPE, $totals);
        $this->assertEquals(
            [
                'type' => 'total',
                'label' => 'Total',
                'amount' => 182.0,
                'currency' => 'USD',
                'visible' => null,
                'data' => null,
            ],
            $totals[TotalProcessorProvider::TYPE]
        );
        $this->assertArrayHasKey(TotalProcessorProvider::SUBTOTALS, $totals);
        $this->assertEquals(
            [
                [
                    'type' => 'subtotal',
                    'label' => 'Total',
                    'amount' => 142.0,
                    'currency' => 'USD',
                    'visible' => null,
                    'data' => null,
                ],
                [
                    'type' => 'subtotal',
                    'label' => 'Total',
                    'amount' => 40.0,
                    'currency' => 'USD',
                    'visible' => null,
                    'data' => null,
                ],
            ],
            $totals[TotalProcessorProvider::SUBTOTALS]
        );
    }
}
