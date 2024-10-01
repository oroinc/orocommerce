<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class SecureTokenIdentifier extends AbstractOption
{
    const SECURETOKENID = 'SECURETOKENID';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureTokenIdentifier::SECURETOKENID)
            ->addAllowedTypes(SecureTokenIdentifier::SECURETOKENID, 'string');
    }
}
