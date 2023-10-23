<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

/**
 * Interface for the shipping line item model.
 *
 * @deprecated since 5.1, use directly {@see ShippingLineItem} instead
 */
interface ShippingLineItemInterface extends
    ProductUnitHolderInterface,
    ProductShippingOptionsInterface,
    ProductHolderInterface,
    QuantityAwareInterface,
    PriceAwareInterface
{
    /**
     * @return Price|null
     */
    public function getPrice();
}
