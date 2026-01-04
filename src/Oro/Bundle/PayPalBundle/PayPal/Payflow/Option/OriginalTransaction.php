<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class OriginalTransaction extends AbstractOption
{
    public const ORIGID = 'ORIGID';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(OriginalTransaction::ORIGID)
            ->addAllowedTypes(OriginalTransaction::ORIGID, 'string');
    }
}
