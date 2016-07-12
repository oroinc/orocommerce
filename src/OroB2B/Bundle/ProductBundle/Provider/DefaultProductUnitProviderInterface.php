<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

interface DefaultProductUnitProviderInterface
{
    /**
     * @return ProductUnitPrecision
     */
    public function getDefaultProductUnitPrecision();
}
