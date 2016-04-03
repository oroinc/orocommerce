<?php

namespace OroB2B\Bundle\PaymentBundle\Model;

interface AmountAwareInterface
{
    /**
     * @return string
     */
    public function getAmount();
}
