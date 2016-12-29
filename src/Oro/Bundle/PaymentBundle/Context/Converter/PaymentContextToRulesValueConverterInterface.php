<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface PaymentContextToRulesValueConverterInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return array
     */
    public function convert(PaymentContextInterface $paymentContext);
}
