<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Invoice extends AbstractOption
{
    const INVNUM = 'INVNUM';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Invoice::INVNUM)
            ->addAllowedTypes(Invoice::INVNUM, 'string');
    }
}
