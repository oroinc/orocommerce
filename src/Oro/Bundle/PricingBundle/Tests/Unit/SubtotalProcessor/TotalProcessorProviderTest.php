<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\CacheAwareSubtotalProviderStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityWithoutCurrencyStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\SubtotalEntityStub;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TotalProcessorProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SUBTOTAL_LABEL = 'oro.pricing.subtotals.total.label (translated)';

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteCurrencyProvider;

    /** @var SubtotalProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $subtotalProviderRegistry;

    /** @var TotalProcessorProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->subtotalProviderRegistry = $this->createMock(SubtotalProviderRegistry::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('round')
            ->willReturnCallback(function ($value) {
                return round($value);
            });

        $this->provider = new TotalProcessorProvider(
            $this->subtotalProviderRegistry,
            $translator,
            $roundingService,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testGetSubtotals(): void
    {
        $entity = $this->prepareSubtotals(new EntityStub());

        $subtotals = $this->provider->enableRecalculation()->getSubtotals($entity);
        self::assertInstanceOf(ArrayCollection::class, $subtotals);
        $subtotal = $subtotals->get(0);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('Total', $subtotal->getLabel());
        self::assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        self::assertSame(142.0, $subtotal->getAmount());
    }

    public function testGetTotal(): void
    {
        $entity = $this->prepareSubtotals(new EntityStub());

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        self::assertInstanceOf(Subtotal::class, $total);
        self::assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $total->getLabel());
        self::assertEquals($entity->getCurrency(), $total->getCurrency());
        self::assertSame(182.0, $total->getAmount());
    }

    public function testGetTotalFromOrder(): void
    {
        /** @var Order $entity */
        $entity = new Order();
        $entity->setTotal(182.5);
        $entity->setCurrency('USD');

        $total = $this->provider->getTotalFromOrder($entity);

        self::assertInstanceOf(Subtotal::class, $total);
        self::assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $total->getLabel());
        self::assertEquals($entity->getCurrency(), $total->getCurrency());
        self::assertSame(182.5, $total->getAmount());
    }

    public function testRecalculationIsEnabledAndProviderIsCacheAware(): void
    {
        $subtotalProvider = $this->createMock(CacheAwareSubtotalProviderStub::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->enableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testRecalculationIsEnabledAndProviderIsNotCacheAware(): void
    {
        $subtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->enableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testRecalculationIsEnabledAndProviderIsSubtotalCacheAware(): void
    {
        $subtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->enableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testProviderIsSubtotalCacheAwareButEntityIsNotShouldFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SubtotalAwareInterface" expected, but "stdClass" given');

        $subtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->setProviderToRegistry($subtotalProvider);

        $this->provider->getSubtotals(new \stdClass());
    }

    public function testRecalculationIsDisabledAndProviderIsSubtotalCacheAware(): void
    {
        $subtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('getCachedSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new SubtotalEntityStub()));
    }

    public function testRecalculationIsDisabledAndProviderIsCacheAware(): void
    {
        $subtotalProvider = $this->createMock(CacheAwareSubtotalProviderStub::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('supportsCachedSubtotal')
            ->willReturn(true);

        $subtotalProvider->expects(self::once())
            ->method('getCachedSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new SubtotalEntityStub()));
    }

    public function testRecalculationIsDisabledAndProviderIsCacheAwareButNotSupported(): void
    {
        $subtotalProvider = $this->createMock(CacheAwareSubtotalProviderStub::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('supportsCachedSubtotal')
            ->willReturn(false);
        $subtotalProvider->expects(self::never())
            ->method('getCachedSubtotal');
        $subtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new SubtotalEntityStub()));
    }

    public function testRecalculationIsDisabledAndProviderIsNotCacheAware(): void
    {
        $subtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->disableRecalculation()->getSubtotals(new EntityStub()));
    }

    public function testRecalculationIsDisabledByDefault(): void
    {
        $subtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->setProviderToRegistry($subtotalProvider);

        $subtotal = new Subtotal();
        $expected = new ArrayCollection([$subtotal]);

        $subtotalProvider->expects(self::once())
            ->method('getCachedSubtotal')
            ->willReturn($subtotal);

        self::assertEquals($expected, $this->provider->getSubtotals(new SubtotalEntityStub()));
    }

    public function testClearSubtotalsCache(): void
    {
        $entity = $this->prepareSubtotals(new EntityStub(), 2);

        $subtotals = $this->provider->enableRecalculation()->getSubtotals($entity);
        self::assertInstanceOf(ArrayCollection::class, $subtotals);
        $subtotal1 = $subtotals->get(0);
        self::assertInstanceOf(Subtotal::class, $subtotal1);

        // try to get again and getProviders and getSubtotal expect run twice
        $subtotals = $this->provider->getSubtotals($entity);
        self::assertInstanceOf(ArrayCollection::class, $subtotals);
        $subtotal1 = $subtotals->get(0);
        $subtotal2 = $subtotals->get(1);

        self::assertInstanceOf(Subtotal::class, $subtotal1);
        self::assertEquals(LineItemSubtotalProvider::TYPE, $subtotal1->getType());
        self::assertEquals('Total', $subtotal1->getLabel());
        self::assertEquals($entity->getCurrency(), $subtotal1->getCurrency());
        self::assertSame(142.0, $subtotal1->getAmount());
        self::assertSame(40.0, $subtotal2->getAmount());
    }

    public function testGetName(): void
    {
        self::assertEquals('oro_pricing.subtotal_total', $this->provider->getName());
    }

    public function testGetTotalInDefaultCurrency(): void
    {
        $entity = new EntityWithoutCurrencyStub();
        $this->currencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $entity = $this->prepareSubtotals($entity);

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        self::assertInstanceOf(Subtotal::class, $total);
        self::assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $total->getLabel());
        self::assertEquals('USD', $total->getCurrency());
        self::assertSame(182.0, $total->getAmount());
    }

    public function testGetTotalSubstractOperation(): void
    {
        $entity = $this->prepareSubtotals(new EntityStub(), 1, Subtotal::OPERATION_SUBTRACTION);

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        self::assertInstanceOf(Subtotal::class, $total);
        self::assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $total->getLabel());
        self::assertEquals($entity->getCurrency(), $total->getCurrency());
        self::assertSame(102.0, $total->getAmount());
    }

    public function testGetTotalSubstractOperationMinLimit(): void
    {
        $entity = $this->prepareSubtotals(new EntityStub(), 1, Subtotal::OPERATION_SUBTRACTION, 200.0);

        $total = $this->provider->enableRecalculation()->getTotal($entity);
        self::assertInstanceOf(Subtotal::class, $total);
        self::assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $total->getLabel());
        self::assertEquals($entity->getCurrency(), $total->getCurrency());
        self::assertSame(0.0, $total->getAmount());
    }

    public function testSubtotalsOrdering(): void
    {
        $firstSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $secondSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);

        $this->subtotalProviderRegistry->expects(self::any())
            ->method('getSupportedProviders')
            ->willReturn([$firstSubtotalProvider, $secondSubtotalProvider]);

        $subtotal1 = (new Subtotal())->setLabel('t1')->setSortOrder(1);
        $subtotal2 = (new Subtotal())->setLabel('t2')->setSortOrder(2);
        $subtotal3 = (new Subtotal())->setLabel('t3')->setSortOrder(3);
        $subtotal4 = (new Subtotal())->setLabel('t4')->setSortOrder(4);
        $subtotal5 = (new Subtotal())->setLabel('t5')->setSortOrder(5);
        $notOrderedSubtotal1 = new Subtotal();
        $notOrderedSubtotal1->setLabel('t10');
        $notOrderedSubtotal2 = new Subtotal();
        $notOrderedSubtotal1->setLabel('t20');

        $firstSubtotalProvider->expects(self::any())
            ->method('getSubtotal')
            ->willReturn([$subtotal2, $notOrderedSubtotal1, $subtotal5, $subtotal1]);

        $secondSubtotalProvider->expects(self::any())
            ->method('getSubtotal')
            ->willReturn([$subtotal4, $subtotal3, $notOrderedSubtotal2]);

        self::assertEquals(
            new ArrayCollection([
                $notOrderedSubtotal1, $notOrderedSubtotal2, $subtotal1, $subtotal2, $subtotal3, $subtotal4, $subtotal5
            ]),
            $this->provider->enableRecalculation()->getSubtotals(new EntityStub())
        );
    }

    private function prepareSubtotals(
        object $entity,
        int $runCount = 1,
        int $operation = Subtotal::OPERATION_ADD,
        float $subtotalAmount = 40.0
    ): object {
        $currency = 'USD';
        $subtotalProvider1 = $this->createMock(LineItemSubtotalProvider::class);
        $subtotalProvider2 = $this->createMock(LineItemSubtotalProvider::class);

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
        $subtotal2->setLabel('Total1');
        $subtotal2->setOperation($operation);

        $this->subtotalProviderRegistry->expects(self::exactly($runCount))
            ->method('getSupportedProviders')
            ->willReturn([$subtotalProvider1, $subtotalProvider2]);
        $subtotalProvider1->expects(self::exactly($runCount))
            ->method('getSubtotal')
            ->willReturn($subtotal1);
        $subtotalProvider2->expects(self::exactly($runCount))
            ->method('getSubtotal')
            ->willReturn($subtotal2);

        return $entity;
    }

    public function testGetTotalWithSubtotalsAsArray(): void
    {
        $entity = $this->prepareSubtotals(new EntityStub());

        $totals = $this->provider->enableRecalculation()->getTotalWithSubtotalsAsArray($entity);
        self::assertIsArray($totals);
        self::assertArrayHasKey(TotalProcessorProvider::TYPE, $totals);
        self::assertEquals(
            [
                'type' => 'total',
                'label' => self::SUBTOTAL_LABEL,
                'amount' => 182.0,
                'signedAmount' => 182.0,
                'currency' => 'USD',
                'visible' => null,
                'data' => null,
            ],
            $totals[TotalProcessorProvider::TYPE]
        );
        self::assertArrayHasKey(TotalProcessorProvider::SUBTOTALS, $totals);
        self::assertEquals(
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
                    'label' => 'Total1',
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

    private function setProviderToRegistry(SubtotalProviderInterface $subtotalProvider): void
    {
        $this->subtotalProviderRegistry->expects(self::once())
            ->method('getSupportedProviders')
            ->willReturn([$subtotalProvider]);
    }

    public function testGetTotalForSubtotals(): void
    {
        $entity = new EntityStub();
        $entity->setCurrency('USD');

        $subtotal = new Subtotal();
        $subtotal->setCurrency('USD');
        $subtotal->setAmount(142.0);
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setLabel('Total');
        $subtotal->setOperation(Subtotal::OPERATION_ADD);

        $total = $this->provider->getTotalForSubtotals($entity, new ArrayCollection([$subtotal]));
        self::assertInstanceOf(Subtotal::class, $total);
        self::assertEquals(TotalProcessorProvider::TYPE, $total->getType());
        self::assertEquals(self::SUBTOTAL_LABEL, $total->getLabel());
        self::assertEquals($entity->getCurrency(), $total->getCurrency());
        self::assertSame(142.0, $total->getAmount());
    }
}
