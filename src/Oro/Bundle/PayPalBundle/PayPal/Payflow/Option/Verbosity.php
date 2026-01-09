<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures verbosity option for PayPal Payflow transactions.
 *
 * Controls the level of detail in PayPal Payflow API responses.
 */
class Verbosity extends AbstractOption
{
    public const VERBOSITY = 'VERBOSITY';

    public const HIGH = 'HIGH';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Verbosity::VERBOSITY)
            ->addAllowedValues(Verbosity::VERBOSITY, [Verbosity::HIGH]);
    }
}
