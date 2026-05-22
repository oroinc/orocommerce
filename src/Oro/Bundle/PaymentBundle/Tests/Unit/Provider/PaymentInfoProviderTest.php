<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Bundle\PaymentBundle\Provider\PaymentInfoProvider;
use Oro\Component\Math\BigDecimal;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentInfoProviderTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private PaymentTransactionRepository&MockObject $transactionRepository;
    private PaymentStatusCalculationHelper&MockObject $paymentStatusCalculationHelper;
    private PaymentStatusManager&MockObject $paymentStatusManager;
    private RoundingServiceInterface&MockObject $roundingService;
    private PaymentInfoProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->transactionRepository = $this->createMock(PaymentTransactionRepository::class);
        $this->paymentStatusCalculationHelper = $this->createMock(PaymentStatusCalculationHelper::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);

        $this->roundingService->expects(self::any())
            ->method('round')
            ->willReturnCallback(static fn (float $amount) => round($amount, 2));

        $this->provider = new PaymentInfoProvider(
            $this->registry,
            $this->paymentStatusCalculationHelper,
            $this->paymentStatusManager,
            $this->roundingService
        );
    }

    public function testGetPaymentStatus(): void
    {
        $entityClass = 'App\Entity\Order';
        $entityId = 5;
        $entity = new \stdClass();

        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus('paid');

        $entityRepository = $this->createMock(ObjectRepository::class);
        $entityRepository->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($entity);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($entityRepository);

        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($entity)
            ->willReturn($paymentStatus);

        self::assertSame($paymentStatus, $this->provider->getPaymentStatus($entityClass, $entityId));
    }

    public function testGetAmountPaidSubtractsRefunds(): void
    {
        $entityClass = 'App\Entity\Order';
        $entityId = 3;

        $this->registry->method('getRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($this->transactionRepository);

        $this->transactionRepository->expects(self::exactly(2))
            ->method('findBy')
            ->withConsecutive(
                [
                    [
                        'entityClass' => $entityClass,
                        'entityIdentifier' => $entityId,
                        'action' => [
                            PaymentMethodInterface::CAPTURE,
                            PaymentMethodInterface::CHARGE,
                            PaymentMethodInterface::PURCHASE,
                        ],
                        'successful' => true,
                    ]
                ],
                [
                    [
                        'entityClass' => $entityClass,
                        'entityIdentifier' => $entityId,
                        'action' => PaymentMethodInterface::REFUND,
                        'successful' => true,
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [(new PaymentTransaction())->setAmount('100.00')],
                [(new PaymentTransaction())->setAmount('30.00')]
            );

        $this->paymentStatusCalculationHelper->expects(self::exactly(2))
            ->method('sumTransactionAmounts')
            ->willReturnOnConsecutiveCalls(
                BigDecimal::of('100.00'),
                BigDecimal::of('30.00')
            );

        self::assertSame(70.0, $this->provider->getAmountPaid($entityClass, $entityId));
    }

    public function testGetAmountPaidClampsToZeroWhenRefundsExceedPaid(): void
    {
        $entityClass = 'App\Entity\Order';
        $entityId = 7;

        $this->registry->method('getRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($this->transactionRepository);

        $this->transactionRepository->expects(self::exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [(new PaymentTransaction())->setAmount('10.00')],
                [(new PaymentTransaction())->setAmount('50.00')]
            );

        // Net would be -40, clamped to 0 before rounding.
        $this->paymentStatusCalculationHelper->expects(self::exactly(2))
            ->method('sumTransactionAmounts')
            ->willReturnOnConsecutiveCalls(
                BigDecimal::of('10.00'),
                BigDecimal::of('50.00')
            );

        self::assertSame(0.0, $this->provider->getAmountPaid($entityClass, $entityId));
    }

    public function testGetAmountDueReturnsCorrectValue(): void
    {
        $entityClass = 'App\Entity\Order';
        $entityId = 10;
        $totalAmount = 200.0;

        $this->registry->method('getRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($this->transactionRepository);

        $this->transactionRepository->expects(self::exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [(new PaymentTransaction())->setAmount('80.00')],
                []
            );

        // Net paid = 80, due = 200 - 80 = 120.
        $this->paymentStatusCalculationHelper->expects(self::exactly(2))
            ->method('sumTransactionAmounts')
            ->willReturnOnConsecutiveCalls(
                BigDecimal::of('80.00'),
                BigDecimal::of(0)
            );

        self::assertSame(120.0, $this->provider->getAmountDue($entityClass, $entityId, $totalAmount));
    }

    public function testGetAmountDueClampsToZeroWhenAmountPaidExceedsTotal(): void
    {
        $entityClass = 'App\Entity\Order';
        $entityId = 11;
        $totalAmount = 50.0;

        $this->registry->method('getRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($this->transactionRepository);

        $this->transactionRepository->expects(self::exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [(new PaymentTransaction())->setAmount('100.00')],
                []
            );

        // Net paid = 100 > totalAmount 50 -> due = 0, clamped.
        $this->paymentStatusCalculationHelper->expects(self::exactly(2))
            ->method('sumTransactionAmounts')
            ->willReturnOnConsecutiveCalls(
                BigDecimal::of('100.00'),
                BigDecimal::of(0)
            );

        self::assertSame(0.0, $this->provider->getAmountDue($entityClass, $entityId, $totalAmount));
    }
}
