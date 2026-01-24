<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Provides base implementation for PayPal Payflow request options.
 *
 * This class serves as a foundation for defining individual options that can be included
 * in PayPal Payflow API requests. Subclasses should override the configureOption method
 * to define specific option validation, normalization, and default values.
 */
abstract class AbstractOption implements OptionInterface
{
    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
    }
}
