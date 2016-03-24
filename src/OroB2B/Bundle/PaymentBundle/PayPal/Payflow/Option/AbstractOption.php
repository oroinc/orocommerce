<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOption implements OptionInterface
{
    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
    }
}
