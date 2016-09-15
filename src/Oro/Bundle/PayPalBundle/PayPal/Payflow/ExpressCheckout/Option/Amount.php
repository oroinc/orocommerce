<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Amount as BaseAmount;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Amount extends BaseAmount implements OptionsDependentInterface
{
    public function __construct()
    {
        parent::__construct(false);
    }

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
            return true;
        }
        
        return in_array($options[Action::ACTION], [Action::SET_EC, Action::DO_EC], true);
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        if (isset($options[Action::ACTION]) &&
            in_array($options[Action::ACTION], [Action::SET_EC, Action::DO_EC], true)
        ) {
            $this->amountRequired = true;
        }

        parent::configureOption($resolver);
    }
}
