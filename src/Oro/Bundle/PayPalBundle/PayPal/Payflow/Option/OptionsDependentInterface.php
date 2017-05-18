<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * This interface could be used to allow dynamic option configuration based on value of another options
 */
interface OptionsDependentInterface extends OptionInterface
{
    /**
     * Check is applicable this option
     *
     * @param mixed[] $options Options to resolve
     * @return bool
     */
    public function isApplicableDependent(array $options);

    /**
     * Configure dependent options
     *
     * @param OptionsResolver $resolver
     * @param mixed[] $options Options to resolve
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options);
}
