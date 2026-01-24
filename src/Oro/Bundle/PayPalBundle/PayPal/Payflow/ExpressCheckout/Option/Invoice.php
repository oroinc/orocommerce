<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Invoice as BaseInvoice;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures invoice number option for PayPal Express Checkout transactions.
 *
 * Extends base invoice option with Express Checkout-specific applicability rules,
 * only allowing invoice configuration for SET_EC and DO_EC actions.
 */
class Invoice extends BaseInvoice implements OptionsDependentInterface
{
    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
    }

    #[\Override]
    public function isApplicableDependent(array $options)
    {
        if (!isset($options[Action::ACTION])) {
            return false;
        }

        return in_array($options[Action::ACTION], [Action::SET_EC, Action::DO_EC], true);
    }

    #[\Override]
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        parent::configureOption($resolver);
    }
}
