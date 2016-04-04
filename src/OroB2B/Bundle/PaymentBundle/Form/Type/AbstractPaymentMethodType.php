<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

abstract class AbstractPaymentMethodType extends AbstractType
{
    /** @return bool */
    abstract public function isMethodEnabled();
}
