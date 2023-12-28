<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see PaymentTermExtensionAwareInterface}.
 */
trait PaymentTermExtensionAwareTrait
{
    /** @var PaymentTermExtension */
    protected $paymentTermExtension;

    public function setPaymentTermExtension(PaymentTermExtension $paymentTermExtension)
    {
        $this->paymentTermExtension = $paymentTermExtension;
    }
}
