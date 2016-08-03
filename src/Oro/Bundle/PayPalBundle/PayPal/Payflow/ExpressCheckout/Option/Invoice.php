<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Invoice as BaseInvoice;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Invoice extends BaseInvoice implements OptionsDependentInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
    }

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
        parent::configureOption($resolver);
    }
}
