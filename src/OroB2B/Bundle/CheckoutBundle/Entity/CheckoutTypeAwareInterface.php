<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

interface CheckoutTypeAwareInterface
{
    /**
     * @return string
     */
    public function getType();
}
