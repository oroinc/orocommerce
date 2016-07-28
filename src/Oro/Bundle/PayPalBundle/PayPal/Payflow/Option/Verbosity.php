<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class Verbosity extends AbstractOption
{
    const VERBOSITY = 'VERBOSITY';

    const HIGH = 'HIGH';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Verbosity::VERBOSITY)
            ->addAllowedValues(Verbosity::VERBOSITY, [Verbosity::HIGH]);
    }
}
