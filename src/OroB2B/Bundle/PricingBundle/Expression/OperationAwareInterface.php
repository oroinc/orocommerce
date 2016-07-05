<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

interface OperationAwareInterface
{
    /**
     * @return string
     */
    public function getOperation();
}
