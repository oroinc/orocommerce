<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Action implements OptionInterface
{
    const ACTION = 'ACTION';

    const SET_EC = 'S';
    const GET_EC_DETAILS = 'G';
    const DO_EC = 'D';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Action::ACTION)
            ->addAllowedValues(Action::ACTION, [Action::SET_EC, Action::GET_EC_DETAILS, Action::DO_EC]);
    }
}
