<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

interface ShippingLineItemInterface extends
    ProductUnitHolderInterface,
    ProductShippingOptionsInterface,
    ProductHolderInterface,
    QuantityAwareInterface,
    PriceAwareInterface
{
}
