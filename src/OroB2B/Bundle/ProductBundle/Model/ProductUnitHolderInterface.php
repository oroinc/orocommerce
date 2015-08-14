<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

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
