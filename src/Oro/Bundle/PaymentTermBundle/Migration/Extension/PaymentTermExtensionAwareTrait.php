<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see PaymentTermExtensionAwareInterface}.
 */
trait PaymentTermExtensionAwareTrait
{
    private PaymentTermExtension $paymentTermExtension;

    public function setPaymentTermExtension(PaymentTermExtension $paymentTermExtension): void
    {
        $this->paymentTermExtension = $paymentTermExtension;
    }
}
