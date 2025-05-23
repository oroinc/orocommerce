<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ShippingAddress as BaseShippingAddress;

class ShippingAddress extends BaseShippingAddress implements OptionsDependentInterface
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
