<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Defines the contract for PayPal Payflow transaction option configuration.
 *
 * Allows classes to configure and customize transaction options for PayPal Payflow requests.
 */
interface OptionsAwareInterface
{
    public function configureOptions(OptionsResolver $resolver);
}
