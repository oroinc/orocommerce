<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

interface OptionsAwareInterface
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver);
}
