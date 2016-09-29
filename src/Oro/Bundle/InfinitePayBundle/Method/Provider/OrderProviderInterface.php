<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface OrderProviderInterface
{
    public function getDataObjectFromPaymentTransaction(PaymentTransaction $paymentTransaction);
}
