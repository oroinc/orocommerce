<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures Express Checkout token option for PayPal transactions.
 *
 * Manages the Express Checkout token parameter, making it required for GET_EC_DETAILS
 * and DO_EC actions while remaining optional for other action types.
 */
class Token extends AbstractOption implements OptionsDependentInterface
{
    const TOKEN = 'TOKEN';

    #[\Override]
    public function isApplicableDependent(array $options)
    {
        return isset($options[Action::ACTION]);
    }

    #[\Override]
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        if (in_array($options[Action::ACTION], [Action::GET_EC_DETAILS, Action::DO_EC], true)) {
            $resolver
                ->setRequired(Token::TOKEN);
        }

        $resolver
            ->setDefined(Token::TOKEN)
            ->addAllowedTypes(Token::TOKEN, 'string');
    }
}
