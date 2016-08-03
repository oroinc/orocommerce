<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Payer extends AbstractOption implements OptionsDependentInterface
{
    const PAYERID = 'PAYERID';

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        return isset($options[Action::ACTION]) && $options[Action::ACTION] === Action::DO_EC;
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $resolver
            ->setDefined(Payer::PAYERID)
            ->addAllowedTypes(Payer::PAYERID, 'string');
    }
}
