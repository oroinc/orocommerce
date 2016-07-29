<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

interface OptionsAwareInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver);
}
