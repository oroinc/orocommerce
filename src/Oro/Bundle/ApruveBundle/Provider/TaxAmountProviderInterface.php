<?php

namespace Oro\Bundle\ApruveBundle\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface TaxAmountProviderInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return float
     */
    public function getTaxAmount(PaymentContextInterface $paymentContext);
}
