<?php

namespace Oro\Bundle\ShippingBundle\Collection;

use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

/**
 * Collection of product shipping options grouped by product ID and unit code.
 *
 * This collection provides efficient lookup of shipping options by product and unit, organizing options in a two-level
 * structure for quick access during shipping calculations and configuration retrieval.
 */
class ProductShippingOptionsGroupedByProductAndUnitCollection
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param ProductShippingOptions $option
     *
     * @return ProductShippingOptionsGroupedByProductAndUnitCollection
     */
    public function add(ProductShippingOptions $option): self
    {
        $this->options[$option->getProduct()->getId()][$option->getProductUnitCode()] = $option;

        return $this;
    }

    /**
     * @param int    $productId
     * @param string $productUnitCode
     *
     * @return ProductShippingOptions|null
     */
    public function get(int $productId, string $productUnitCode)
    {
        if (!isset($this->options[$productId][$productUnitCode])) {
            return null;
        }

        return $this->options[$productId][$productUnitCode];
    }
}
