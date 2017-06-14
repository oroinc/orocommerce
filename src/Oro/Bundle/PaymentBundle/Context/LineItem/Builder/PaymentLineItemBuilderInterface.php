<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Builder;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

interface PaymentLineItemBuilderInterface
{
    /**
     * @return PaymentLineItemInterface
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
     * @param Price $price
     *
     * @return self
     */
    public function setPrice(Price $price);
}
