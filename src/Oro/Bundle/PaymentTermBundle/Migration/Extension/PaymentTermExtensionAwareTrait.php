<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

trait PaymentTermExtensionAwareTrait
{
    /** @var PaymentTermExtension */
    protected $paymentTermExtension;

    /**
     * @param PaymentTermExtension $paymentTermExtension
     */
    public function setPaymentTermExtension(PaymentTermExtension $paymentTermExtension)
    {
        $this->paymentTermExtension = $paymentTermExtension;
    }
}
