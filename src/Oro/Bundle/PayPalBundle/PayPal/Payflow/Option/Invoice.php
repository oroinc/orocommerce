<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Invoice options
 */
class Invoice extends AbstractOption
{
    public const INVNUM = 'INVNUM';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Invoice::INVNUM)
            ->addAllowedTypes(Invoice::INVNUM, 'string');
    }
}
