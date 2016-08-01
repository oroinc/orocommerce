<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class PaymentType extends AbstractOption implements OptionsDependentInterface
{
    const PAYMENTTYPE = 'PAYMENTTYPE';

    const INSTANTONLY = 'instantonly';
    const ANY = 'any';

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        if (!isset($options[Action::ACTION])) {
            return false;
        }
        return in_array($options[Action::ACTION], [Action::SET_EC, Action::DO_EC], true);
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $resolver
            ->setDefined(PaymentType::PAYMENTTYPE)
            ->addAllowedValues(PaymentType::PAYMENTTYPE, [PaymentType::INSTANTONLY, PaymentType::ANY]);
    }
}
