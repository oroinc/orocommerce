<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures payment type option for PayPal Express Checkout transactions.
 *
 * Restricts payment type to instantonly or any, applicable only for SET_EC and DO_EC actions.
 */
class PaymentType extends AbstractOption implements OptionsDependentInterface
{
    const PAYMENTTYPE = 'PAYMENTTYPE';

    const INSTANTONLY = 'instantonly';
    const ANY = 'any';

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
        $resolver
            ->setDefined(PaymentType::PAYMENTTYPE)
            ->addAllowedValues(PaymentType::PAYMENTTYPE, [PaymentType::INSTANTONLY, PaymentType::ANY]);
    }
}
