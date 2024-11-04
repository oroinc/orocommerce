<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

abstract class AbstractDependentOption implements OptionsDependentInterface
{
    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
    }

    #[\Override]
    public function isApplicableDependent(array $options)
    {
    }

    #[\Override]
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
    }
}
