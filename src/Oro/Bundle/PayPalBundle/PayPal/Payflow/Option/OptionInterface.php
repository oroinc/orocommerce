<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

interface OptionInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOption(OptionsResolver $resolver);
}
