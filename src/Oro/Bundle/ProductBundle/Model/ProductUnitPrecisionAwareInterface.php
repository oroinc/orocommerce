<?php

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Interface for models aware of their product unit precision.
 */
interface ProductUnitPrecisionAwareInterface
{
    public function getProductUnitPrecision(): int;
}
