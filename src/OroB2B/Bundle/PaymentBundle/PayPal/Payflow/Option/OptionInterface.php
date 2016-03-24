<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface OptionInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOption(OptionsResolver $resolver);
}
