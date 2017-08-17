<?php

namespace Oro\Bundle\ShippingBundle\Collection;

use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

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
