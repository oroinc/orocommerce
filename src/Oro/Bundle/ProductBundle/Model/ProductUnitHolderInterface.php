<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Defines the contract for entities that hold a reference to a product unit.
 *
 * Implementations of this interface represent entities that are associated with both a product
 * (via {@see ProductHolderInterface}) and a specific product unit,
 * providing standardized access to unit information for quantity-based operations.
 */
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
