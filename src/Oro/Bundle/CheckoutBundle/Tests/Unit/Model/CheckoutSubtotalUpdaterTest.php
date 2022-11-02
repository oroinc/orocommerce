<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

class CheckoutSubtotalUpdaterTest extends \PHPUnit\Framework\TestCase
{
    const USD = 'USD';
    const EUR = 'EUR';
    const CAD = 'CAD';

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $objectManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var CheckoutSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $subtotalProvider;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $currencyManager;

    /** @var CheckoutSubtotalUpdater */
    protected $checkoutSubtotalUpdater;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->subtotalProvider = $this->createMock(CheckoutSubtotalProvider::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->checkoutSubtotalUpdater = new CheckoutSubtotalUpdater(
            $this->registry,
            $this->subtotalProvider,
            $this->currencyManager
        );

        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(CheckoutSubtotal::class)
            ->willReturn($this->objectManager);
    }

    public function testRecalculateCheckoutSubtotals()
    {
        $checkout = $this->createMock(Checkout::class);
        $totalUsd = new CheckoutSubtotal($checkout, self::USD);
        $totalEur = new CheckoutSubtotal($checkout, self::EUR);
        $checkout->expects($this->once())->method('getSubtotals')->willReturn([$totalUsd, $totalEur]);
        $this->currencyManager->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn([self::EUR, self::USD, self::CAD]);

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->once())->method('flush');

        $combinedPriceList1 = (new CombinedPriceList())->setName('price list 1');
        $combinedPriceList2 = (new CombinedPriceList())->setName('price list 2');
        $this->subtotalProvider->expects($this->exactly(3))
            ->method('getSubtotalByCurrency')
            ->willReturnMap([
                [
                    $checkout,
                    self::USD,
                    (new Subtotal())
                        ->setCurrency(self::USD)->setAmount(100)->setPriceList($combinedPriceList1)
                ],
                [
                    $checkout,
                    self::EUR,
                    (new Subtotal())
                        ->setCurrency(self::EUR)->setAmount(80)->setPriceList($combinedPriceList2)
                ],
                [$checkout, self::CAD, (new Subtotal())->setCurrency(self::CAD)->setAmount(120)],
            ]);

        $this->checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout, true);
        $this->assertSame(100.0, $totalUsd->getSubtotal()->getAmount());
        $this->assertSame(80.0, $totalEur->getSubtotal()->getAmount());

        $this->assertSame('price list 1', $totalUsd->getSubtotal()->getPriceList()->getName());
        $this->assertSame('price list 2', $totalEur->getSubtotal()->getPriceList()->getName());
    }

    public function testRecalculateInvalidSubtotals()
    {
        $checkout = $this->createMock(Checkout::class);
        $totalUsd = new CheckoutSubtotal($checkout, self::USD);
        $totalEur = new CheckoutSubtotal($checkout, self::EUR);
        $checkout->expects($this->once())->method('getSubtotals')->willReturn([$totalUsd, $totalEur]);
        $repository = $this->createMock(CheckoutRepository::class);
        $repository->expects($this->once())->method('findWithInvalidSubtotals')->willReturn([$checkout]);
        $this->objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Checkout::class)
            ->willReturn($repository);
        $this->currencyManager->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn([self::EUR, self::USD, self::CAD]);

        $this->objectManager->expects($this->once())->method('persist');
        $this->objectManager->expects($this->once())->method('flush');
        $this->objectManager->expects($this->once())->method('clear');

        $this->subtotalProvider->expects($this->exactly(3))
            ->method('getSubtotalByCurrency')
            ->willReturnMap([
                [$checkout, self::USD, (new Subtotal())->setCurrency(self::USD)->setAmount(100)],
                [$checkout, self::EUR, (new Subtotal())->setCurrency(self::EUR)->setAmount(80)],
                [$checkout, self::CAD, (new Subtotal())->setCurrency(self::CAD)->setAmount(120)],
            ]);

        $this->checkoutSubtotalUpdater->recalculateInvalidSubtotals();
        $this->assertSame(100.0, $totalUsd->getSubtotal()->getAmount());
        $this->assertSame(80.0, $totalEur->getSubtotal()->getAmount());
    }

    public function testRecalculateInvalidSubtotalsNoCheckouts()
    {
        $repository = $this->createMock(CheckoutRepository::class);
        $repository->expects($this->once())->method('findWithInvalidSubtotals')->willReturn([]);
        $this->objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Checkout::class)
            ->willReturn($repository);
        $this->currencyManager->expects($this->once())->method('getAvailableCurrencies');

        $this->objectManager->expects($this->never())->method('persist');
        $this->objectManager->expects($this->never())->method('flush');
        $this->objectManager->expects($this->never())->method('clear');
        $this->subtotalProvider->expects($this->never())->method('getSubtotalByCurrency');

        $this->checkoutSubtotalUpdater->recalculateInvalidSubtotals();
    }
}
