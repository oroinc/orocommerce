<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

abstract class AbstractOption implements OptionInterface
{
    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
    }
}
