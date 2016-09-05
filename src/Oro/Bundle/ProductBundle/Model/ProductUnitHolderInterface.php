<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;

interface ProductUnitHolderInterface
{
    /**
     * Get id
     *
     * @return mixed
     */
    public function getEntityIdentifier();

    /**
     * Get productHolder
     *
     * @return ProductHolderInterface
     */
    public function getProductHolder();

    /**
     * Get product
     *
     * @return ProductUnit
     */
    public function getProductUnit();

    /**
     * Get productUnitCode
     *
     * @return string
     */
    public function getProductUnitCode();
}
