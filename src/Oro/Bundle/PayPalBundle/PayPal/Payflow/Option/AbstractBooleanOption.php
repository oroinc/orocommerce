<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

abstract class AbstractBooleanOption extends AbstractOption
{
    const YES = 'Y';
    const NO = 'N';

    const TRUE = 'TRUE';
    const FALSE = 'FALSE';

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
