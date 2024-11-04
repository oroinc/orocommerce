<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class DefaultPaymentResultMessageProvider implements PaymentResultMessageProviderInterface
{
    #[\Override]
    public function getErrorMessage(PaymentTransaction $transaction = null)
    {
        return 'oro.payment.result.error';
    }
}
