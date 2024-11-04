<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

abstract class AbstractOption implements OptionInterface
{
    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
    }
}
