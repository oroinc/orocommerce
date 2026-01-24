<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures action option for PayPal Express Checkout transactions.
 *
 * Defines the Express Checkout action type (SET_EC, GET_EC_DETAILS, DO_EC),
 * controlling the flow and behavior of the Express Checkout process.
 */
class Action implements OptionInterface
{
    const ACTION = 'ACTION';

    const SET_EC = 'S';
    const GET_EC_DETAILS = 'G';
    const DO_EC = 'D';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Action::ACTION)
            ->addAllowedValues(Action::ACTION, [Action::SET_EC, Action::GET_EC_DETAILS, Action::DO_EC]);
    }
}
