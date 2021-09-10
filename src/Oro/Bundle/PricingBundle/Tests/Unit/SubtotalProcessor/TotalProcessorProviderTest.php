<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityWithoutCurrencyStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\SubtotalEntityStub;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TotalProcessorProviderTest extends AbstractSubtotalProviderTest
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SubtotalProviderRegistry
     */
    protected $subtotalProviderRegistry;

    /**
     * @var TotalProcessorProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RoundingServiceInterface
     */
    protected $roundingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subtotalProviderRegistry =
            $this->createMock('Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry');

        $this->translator = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');

        $this->roundingService = $this->createMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
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
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    protected function tearDown(): void
    {
        unset($this->translator, $this->provider);
    }

    public function testGetSubtotals()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $subtotals = $this->provider->enableRecalculation()->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal = $subtotals->get(0);

        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals(142.0, $subtotal->getAmount());
    }

    public function testGetSubtotalsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->provider->getSubtotals(null);
    }

    public function testGetTotal()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertIsFloat($total->getAmount());
        $this->assertEquals(182.0, $total->getAmount());
    }

    public function testRecalculationIsEnabledAndProviderIsCacheAware()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->enableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testRecalculationIsEnabledAndProviderIsNotCacheAware()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->enableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testRecalculationIsEnabledAndProviderIsSubtotalCacheAware()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->enableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testProviderIsSubtotalCacheAwareButEntityIsNotShouldFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SubtotalAwareInterface" expected, but "stdClass" given');

        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $this->provider->getSubtotals(new \stdClass());
    }

    public function testRecalculationIsDisabledAndProviderIsSubtotalCacheAware()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('getCachedSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new SubtotalEntityStub()));
    }

    public function testRecalculationIsDisabledAndProviderIsCacheAware()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('supportsCachedSubtotal')
            ->willReturn(true);

        $subtotalProvider
            ->expects($this->once())
            ->method('getCachedSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new SubtotalEntityStub()));
    }

    public function testRecalculationIsDisabledAndProviderIsCacheAwareButNotSupported()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('supportsCachedSubtotal')
            ->willReturn(false);

        $subtotalProvider
            ->expects($this->never())
            ->method('getCachedSubtotal');

        $subtotalProvider
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new SubtotalEntityStub()));
    }

    public function testRecalculationIsDisabledAndProviderIsNotCacheAware()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testRecalculationIsDisabledByDefault()
    {
        /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject $subtotalProvider */
        $subtotalProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider
            ->expects($this->once())
            ->method('getCachedSubtotal')
            ->willReturn($subtotal);

        $this->assertEquals($expected, $this->provider->getSubtotals(new SubtotalEntityStub()));
    }

    public function testClearSubtotalsCache()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub(), 2);

        $subtotals = $this->provider->enableRecalculation()->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal1 = $subtotals->get(0);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal1);

        // try to get again and getProviders and getSubtotal expect run twice
        $subtotals = $this->provider->getSubtotals($entity);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);
        $subtotal1 = $subtotals->get(0);
        $subtotal2 = $subtotals->get(1);

        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal1);
        $this->assertEquals(LineItemSubtotalProvider::TYPE, $subtotal1->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $subtotal1->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal1->getCurrency());
        $this->assertIsFloat($subtotal1->getAmount());
        $this->assertEquals(142.0, $subtotal1->getAmount());
        $this->assertEquals(40.0, $subtotal2->getAmount());
    }

    public function testGetName()
    {
        $this->assertEquals(TotalProcessorProvider::NAME, $this->provider->getName());
    }

    public function testGetTotalInDefaultCurrency()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = new EntityWithoutCurrencyStub();
        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $entity = $this->prepareSubtotals($entity);

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals('USD', $total->getCurrency());
        $this->assertIsFloat($total->getAmount());
        $this->assertEquals(182.0, $total->getAmount());
    }

    public function testGetTotalSubstractOperation()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub(), 1, Subtotal::OPERATION_SUBTRACTION);

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertIsFloat($total->getAmount());
        $this->assertEquals(102.0, $total->getAmount());
    }

    public function testGetTotalSubstractOperationMinLimit()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub(), 1, Subtotal::OPERATION_SUBTRACTION, 200.0);

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertIsFloat($total->getAmount());
        $this->assertEquals(0.0, $total->getAmount());
    }

    public function testSubtotalsOrdering()
    {
        /** @var LineItemSubtotalProvider $subtotalProvider */
        $firstSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        /** @var LineItemSubtotalProvider $subtotalProvider */
        $secondSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->subtotalProviderRegistry
            ->expects($this->any())
            ->method('getSupportedProviders')
            ->willReturn([$firstSubtotalProvider, $secondSubtotalProvider]);

        $subtotal1 = (new Subtotal())->setSortOrder(1);
        $subtotal2 = (new Subtotal())->setSortOrder(2);
        $subtotal3 = (new Subtotal())->setSortOrder(3);
        $subtotal4 = (new Subtotal())->setSortOrder(4);
        $subtotal5 = (new Subtotal())->setSortOrder(5);
        $notOrderedSubtotal1 = new Subtotal();
        $notOrderedSubtotal2 = new Subtotal();

        $firstSubtotalProvider
            ->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([$subtotal2, $notOrderedSubtotal1, $subtotal5, $subtotal1]);

        $secondSubtotalProvider
            ->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([$subtotal4, $subtotal3, $notOrderedSubtotal2]);

        $this->assertEquals(
            new ArrayCollection([
                $notOrderedSubtotal1, $notOrderedSubtotal2, $subtotal1, $subtotal2, $subtotal3, $subtotal4, $subtotal5
            ]),
            $this->provider->enableRecalculation()->getSubtotals(new EntityStub())
        );
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
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $subtotalProvider2 = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider'
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
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));

        $entity = $this->prepareSubtotals(new EntityStub());

        $totals = $this->provider->enableRecalculation()->getTotalWithSubtotalsAsArray($entity);
        $this->assertIsArray($totals);
        $this->assertArrayHasKey(TotalProcessorProvider::TYPE, $totals);
        $this->assertEquals(
            [
                'type' => 'total',
                'label' => 'Total',
                'amount' => 182.0,
                'signedAmount' => 182.0,
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
                    'signedAmount' => 142.0,
                    'currency' => 'USD',
                    'visible' => null,
                    'data' => null,
                ],
                [
                    'type' => 'subtotal',
                    'label' => 'Total',
                    'amount' => 40.0,
                    'signedAmount' => 40.0,
                    'currency' => 'USD',
                    'visible' => null,
                    'data' => null,
                ],
            ],
            $totals[TotalProcessorProvider::SUBTOTALS]
        );
    }

    protected function setProviderToRegistry(SubtotalProviderInterface $subtotalProvider)
    {
        $this->subtotalProviderRegistry
            ->expects($this->once())
            ->method('getSupportedProviders')
            ->willReturn([$subtotalProvider]);
    }

    public function testGetTotalForSubtotals()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(sprintf('oro.pricing.subtotals.%s.label', TotalProcessorProvider::TYPE))
            ->willReturn(ucfirst(TotalProcessorProvider::TYPE));
        $entity = new EntityStub();
        $entity->setCurrency('USD');
        $subtotal = new Subtotal();
        $subtotal->setCurrency('USD');
        $subtotal->setAmount(142.0);
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setLabel('Total');
        $subtotal->setOperation(Subtotal::OPERATION_ADD);
        $total = $this->provider->enableRecalculation()->getTotalForSubtotals($entity, [$subtotal]);
        $this->assertInstanceOf(Subtotal::class, $total);
        $this->assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        $this->assertEquals(ucfirst(TotalProcessorProvider::TYPE), $total->getLabel());
        $this->assertEquals($entity->getCurrency(), $total->getCurrency());
        $this->assertIsFloat($total->getAmount());
        $this->assertEquals(142.0, $total->getAmount());
    }
}
