<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Defines the contract for retrieving the default product unit precision.
 *
 * Implementations of this interface provide the default product unit and precision to be used
 * when creating new products or in contexts where no specific unit is specified.
 */
interface DefaultProductUnitProviderInterface
{
    /**
     * @return ProductUnitPrecision|null
     */
    public function getDefaultProductUnitPrecision();
}
