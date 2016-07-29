<?php

namespace Oro\Bundle\PayPalBundle\Method\View;

use Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro;

class PayPalPaymentsProView extends PayflowGatewayView
{
    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PayPalPaymentsPro::TYPE;
    }
}
