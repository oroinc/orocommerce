<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

interface ShippingLineItemBuilderInterface
{
    /**
     * @return ShippingLineItemInterface
     */
    public function getResult();

    /**
     * @param Product $product
     *
     * @return self
     */
    public function setProduct(Product $product);

    /**
     * @param string $sku
     *
     * @return self
     */
    public function setProductSku($sku);

    /**
     * @param Dimensions $dimensions
     *
     * @return self
     */
    public function setDimensions(Dimensions $dimensions);

    /**
     * @param Weight $weight
     *
     * @return self
     */
    public function setWeight(Weight $weight);

    /**
     * @param Price $price
     *
     * @return self
     */
    public function setPrice(Price $price);
}
