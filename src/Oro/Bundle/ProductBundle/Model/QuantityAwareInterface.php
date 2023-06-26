<?php

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Interface for line item models aware of their quantity.
 */
interface QuantityAwareInterface
{
    /**
     * @return float|int
     */
    public function getQuantity();
}
