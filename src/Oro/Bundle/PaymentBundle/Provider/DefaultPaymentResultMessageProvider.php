<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class DefaultPaymentResultMessageProvider implements PaymentResultMessageProviderInterface
{
    /** {@inheritdoc} */
    public function getErrorMessage(PaymentTransaction $transaction = null)
    {
        return 'oro.payment.result.error';
    }
}
