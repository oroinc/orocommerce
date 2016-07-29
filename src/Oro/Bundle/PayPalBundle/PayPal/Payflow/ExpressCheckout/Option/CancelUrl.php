<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\CancelUrl as BaseCancelUrl;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class CancelUrl extends BaseCancelUrl implements OptionsDependentInterface
{
    const CANCELURL = 'CANCELURL';

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct(true);
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
        return isset($options[Action::ACTION]) && $options[Action::ACTION] === Action::SET_EC;
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        parent::configureOption($resolver);
    }
}
