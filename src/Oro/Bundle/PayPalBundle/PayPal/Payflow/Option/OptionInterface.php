<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Defines the contract for PayPal Payflow transaction options.
 *
 * Provides configuration interface for transaction options that can be resolved and validated.
 */
interface OptionInterface
{
    public function configureOption(OptionsResolver $resolver);
}
