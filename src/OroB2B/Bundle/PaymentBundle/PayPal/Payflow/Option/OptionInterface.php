<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

interface OptionInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOption(OptionsResolver $resolver);
}
