<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class ShippingAddressOverride extends AbstractBooleanOption implements OptionsDependentInterface
{
    const ADDROVERRIDE = 'ADDROVERRIDE';

    const TRUE = 1;
    const FALSE = 0;

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        return isset($options[Action::ACTION]) && $options[Action::ACTION] === Action::SET_EC;
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $resolver
            ->setDefined(ShippingAddressOverride::ADDROVERRIDE)
            ->setNormalizer(
                ShippingAddressOverride::ADDROVERRIDE,
                $this->getNormalizer(ShippingAddressOverride::TRUE, ShippingAddressOverride::FALSE)
            );
    }
}
