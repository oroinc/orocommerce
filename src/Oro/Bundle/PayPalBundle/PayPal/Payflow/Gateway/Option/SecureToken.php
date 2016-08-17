<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class SecureToken extends AbstractOption
{
    const SECURETOKEN = 'SECURETOKEN';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureToken::SECURETOKEN)
            ->addAllowedTypes(SecureToken::SECURETOKEN, 'string');
    }
}
