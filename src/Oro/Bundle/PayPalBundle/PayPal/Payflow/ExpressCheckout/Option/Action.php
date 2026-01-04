<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Action implements OptionInterface
{
    public const ACTION = 'ACTION';

    public const SET_EC = 'S';
    public const GET_EC_DETAILS = 'G';
    public const DO_EC = 'D';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Action::ACTION)
            ->addAllowedValues(Action::ACTION, [Action::SET_EC, Action::GET_EC_DETAILS, Action::DO_EC]);
    }
}
