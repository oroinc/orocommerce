<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

interface OptionInterface
{
    public function configureOption(OptionsResolver $resolver);
}
