<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Component\Math\BigDecimal;

/**
 * Provides the net amount paid and the remaining amount due for any entity.
 *
 * "amountPaid" is the sum of all successful CAPTURE, CHARGE, and PURCHASE transactions
 * minus the sum of all successful REFUND transactions.
 * "amountDue" is the difference between the entity total amount and the net amount paid,
 * clamped to zero.
 */
class PaymentInfoProvider implements PaymentInfoProviderInterface
{
    private const array PAID_ACTIONS = [
        PaymentMethodInterface::CAPTURE,
        PaymentMethodInterface::CHARGE,
        PaymentMethodInterface::PURCHASE,
    ];

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly PaymentStatusCalculationHelper $paymentStatusCalculationHelper,
        private readonly PaymentStatusManager $paymentStatusManager,
        private readonly RoundingServiceInterface $roundingService
    ) {
    }

    public function getPaymentStatus(string $entityClass, int $entityId): PaymentStatus
    {
        $entity = $this->registry->getRepository($entityClass)->find($entityId);

        return $this->paymentStatusManager->getPaymentStatus($entity);
    }

    public function getAmountPaid(string $entityClass, int $entityId): float
    {
        $net = $this->calculateNetAmountPaid($entityClass, $entityId);

        return $this->roundingService->round(max(0.0, $net->toFloat()));
    }

    public function getAmountDue(string $entityClass, int $entityId, float $totalAmount): float
    {
        $amountPaid = $this->calculateNetAmountPaid($entityClass, $entityId);
        $due = BigDecimal::of($totalAmount)->minus($amountPaid);

        return $this->roundingService->round(max(0.0, $due->toFloat()));
    }

    private function calculateNetAmountPaid(string $entityClass, int $entityId): BigDecimal
    {
        /** @var PaymentTransactionRepository $transactionRepo */
        $transactionRepo = $this->registry->getRepository(PaymentTransaction::class);
        $paidTransactions = $transactionRepo->findBy([
            'entityClass' => $entityClass,
            'entityIdentifier' => $entityId,
            'action' => self::PAID_ACTIONS,
            'successful' => true,
        ]);
        $grossPaid = $this->paymentStatusCalculationHelper->sumTransactionAmounts($paidTransactions);

        $refundTransactions = $transactionRepo->findBy([
            'entityClass' => $entityClass,
            'entityIdentifier' => $entityId,
            'action' => PaymentMethodInterface::REFUND,
            'successful' => true,
        ]);
        $refunded = $this->paymentStatusCalculationHelper->sumTransactionAmounts($refundTransactions);

        return $grossPaid->minus($refunded);
    }
}
