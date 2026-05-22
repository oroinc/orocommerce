<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;

/**
 * Provides the net amount paid and the remaining amount due for any entity.
 *
 * "amountPaid" is the sum of all successful CAPTURE, CHARGE, and PURCHASE transactions
 * minus the sum of all successful REFUND transactions.
 * "amountDue" is the difference between the entity total amount and the net amount paid,
 * clamped to zero.
 */
interface PaymentInfoProviderInterface
{
    public function getPaymentStatus(string $entityClass, int $entityId): PaymentStatus;

    public function getAmountPaid(string $entityClass, int $entityId): float;

    public function getAmountDue(string $entityClass, int $entityId, float $totalAmount): float;
}
