<?php

namespace Oro\Bundle\PaymentBundle\Method\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Payment Method with Capture support
 */
interface CaptureActionInterface
{
    public function capture(PaymentTransaction $paymentTransaction): array;

    /**
     * Configure source transaction action, e.g. authorize, pending
     */
    public function getSourceAction(): string;

    /**
     * Create new transaction when false, use existing pending transaction when true
     */
    public function useSourcePaymentTransaction(): bool;
}
