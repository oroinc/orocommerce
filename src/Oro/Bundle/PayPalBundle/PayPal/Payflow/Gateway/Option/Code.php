<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures CVV2 security code option for PayPal Payflow Gateway transactions.
 *
 * Manages the card verification value (CVV2) for credit card validation.
 */
class Code implements OptionInterface
{
    public const CVV2 = 'CVV2';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Code::CVV2)
            ->addAllowedTypes(Code::CVV2, 'string');
    }
}
