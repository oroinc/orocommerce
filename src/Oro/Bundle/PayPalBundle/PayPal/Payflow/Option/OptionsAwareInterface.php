<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

interface OptionsAwareInterface
{
    public function configureOptions(OptionsResolver $resolver);
}
