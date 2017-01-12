<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

interface DefaultProductUnitProviderInterface
{
    /**
     * @return ProductUnitPrecision|null
     */
    public function getDefaultProductUnitPrecision();
}
