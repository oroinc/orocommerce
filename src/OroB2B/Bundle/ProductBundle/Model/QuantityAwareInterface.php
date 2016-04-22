<?php

namespace OroB2B\Bundle\ProductBundle\Model;

interface QuantityAwareInterface
{
    /**
     * @return int
     */
    public function getQuantity();
}
