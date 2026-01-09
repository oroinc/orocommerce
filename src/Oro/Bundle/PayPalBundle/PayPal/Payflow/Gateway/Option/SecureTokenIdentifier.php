<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures secure token identifier option for PayPal Payflow Gateway transactions.
 *
 * Manages the unique identifier for a previously created secure token.
 */
class SecureTokenIdentifier extends AbstractOption
{
    public const SECURETOKENID = 'SECURETOKENID';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SecureTokenIdentifier::SECURETOKENID)
            ->addAllowedTypes(SecureTokenIdentifier::SECURETOKENID, 'string');
    }
}
