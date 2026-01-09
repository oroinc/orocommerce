<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl as BaseReturnUrl;

/**
 * Configures return URL option for PayPal Express Checkout transactions.
 *
 * Extends base return URL option with Express Checkout-specific applicability,
 * requiring the return URL only for SET_EC actions.
 */
class ReturnUrl extends BaseReturnUrl implements OptionsDependentInterface
{
    public const RETURNURL = 'RETURNURL';

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
