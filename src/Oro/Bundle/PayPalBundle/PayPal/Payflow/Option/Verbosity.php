<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

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
