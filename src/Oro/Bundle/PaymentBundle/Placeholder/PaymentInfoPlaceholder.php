<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Placeholder;

use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;

/**
 * Service implementation for "payment_info" placeholder.
 */
class PaymentInfoPlaceholder
{
    public function __construct(private readonly PaymentStatusManager $paymentStatusManager)
    {
    }

    public function getPaymentStatus(object $entity): string
    {
        return (string)$this->paymentStatusManager->getPaymentStatus($entity);
    }
}
