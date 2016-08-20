<?php

namespace Oro\Bundle\ProductBundle\Model;

interface QuantityAwareInterface
{
    /**
     * @return int
     */
    public function getQuantity();
}
