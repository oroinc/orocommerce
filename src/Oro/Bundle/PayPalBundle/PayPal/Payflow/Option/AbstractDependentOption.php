<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Provides base implementation for PayPal Payflow options that depend on other options.
 *
 * This class serves as a foundation for options whose configuration and applicability
 * depend on the values of other options in the request. Subclasses should override the methods
 * to define specific dependency logic and conditional configuration.
 */
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
