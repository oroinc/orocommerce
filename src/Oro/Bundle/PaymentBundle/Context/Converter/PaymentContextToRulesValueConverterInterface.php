<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Describes a converter from {@see PaymentContextInterface} to an array.
 */
interface PaymentContextToRulesValueConverterInterface
{
    public function convert(PaymentContextInterface $paymentContext): array;
}
