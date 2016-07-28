<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class ExpirationDate extends AbstractOption
{
    const EXPDATE = 'EXPDATE';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ExpirationDate::EXPDATE)
            ->addAllowedTypes(ExpirationDate::EXPDATE, 'string');
    }
}
