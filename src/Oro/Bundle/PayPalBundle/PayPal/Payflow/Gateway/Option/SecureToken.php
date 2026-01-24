<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures secure token option for PayPal Payflow Gateway transactions.
 *
 * Manages the secure token value for transparent redirect payment processing.
 */
class SecureToken extends AbstractOption
{
    const SECURETOKEN = 'SECURETOKEN';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureToken::SECURETOKEN)
            ->addAllowedTypes(SecureToken::SECURETOKEN, 'string');
    }
}
