<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl as BaseReturnUrl;

class ReturnUrl extends BaseReturnUrl implements OptionsDependentInterface
{
    const RETURNURL = 'RETURNURL';

    public function __construct()
    {
        parent::__construct(true);
    }

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
    }

    #[\Override]
    public function isApplicableDependent(array $options)
    {
        return isset($options[Action::ACTION]) && $options[Action::ACTION] === Action::SET_EC;
    }

    #[\Override]
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        parent::configureOption($resolver);
    }
}
