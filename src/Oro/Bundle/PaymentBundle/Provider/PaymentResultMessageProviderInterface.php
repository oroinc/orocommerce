<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentResultMessageProviderInterface
{
    /**
     * @param PaymentTransaction|null $transaction
     * @return string
     */
    public function getErrorMessage(PaymentTransaction $transaction = null);
}
