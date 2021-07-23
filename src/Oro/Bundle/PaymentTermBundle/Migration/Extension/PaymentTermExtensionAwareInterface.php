<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

/**
 * PaymentTermExtensionAwareInterface should be implemented by migrations that depends on a PaymentTermExtension.
 */
interface PaymentTermExtensionAwareInterface
{
    public function setPaymentTermExtension(PaymentTermExtension $paymentTermExtension);
}
