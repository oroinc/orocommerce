<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

/**
 * PaymentTermExtensionAwareInterface should be implemented by migrations that depends on a PaymentTermExtension.
 */
interface PaymentTermExtensionAwareInterface
{
    /**
     * @param PaymentTermExtension $paymentTermExtension
     */
    public function setPaymentTermExtension(PaymentTermExtension $paymentTermExtension);
}
