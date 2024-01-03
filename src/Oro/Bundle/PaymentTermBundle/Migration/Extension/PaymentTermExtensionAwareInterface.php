<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see PaymentTermExtension}.
 */
interface PaymentTermExtensionAwareInterface
{
    public function setPaymentTermExtension(PaymentTermExtension $paymentTermExtension);
}
