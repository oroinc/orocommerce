<?php

namespace Oro\Bundle\PayPalBundle\Method\View;

use Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout;

class PayPalPaymentsProExpressCheckoutView extends PayflowExpressCheckoutView
{
    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PayPalPaymentsProExpressCheckout::TYPE;
    }
}
