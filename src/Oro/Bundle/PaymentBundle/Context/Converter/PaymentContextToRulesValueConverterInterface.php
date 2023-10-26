<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Describes a converter from {@see PaymentContextInterface} to an array.
 */
interface PaymentContextToRulesValueConverterInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return array
     */
    public function convert(PaymentContextInterface $paymentContext);
}
