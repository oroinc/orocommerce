<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Provides common functionality for PayPal Payflow boolean options.
 *
 * This base class handles the normalization of boolean values to PayPal-specific string representations
 * (such as 'Y'/'N' or 'TRUE'/'FALSE'). Subclasses should use the provided normalizer to ensure
 * boolean options are properly formatted for the PayPal Payflow API.
 */
abstract class AbstractBooleanOption extends AbstractOption
{
    public const YES = 'Y';
    public const NO = 'N';

    public const TRUE = 'TRUE';
    public const FALSE = 'FALSE';

    /**
     * @param string $true
     * @param string $false
     * @return \Closure
     */
    protected function getNormalizer($true, $false)
    {
        return function (OptionsResolver $resolver, $value) use ($true, $false) {
            if ($value == true) {
                return $true;
            }

            return $false;
        };
    }
}
