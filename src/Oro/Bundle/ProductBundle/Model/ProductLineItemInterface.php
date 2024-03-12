<?php

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Interface for a product line item.
 */
interface ProductLineItemInterface extends
    ProductHolderInterface,
    ProductUnitHolderInterface,
    QuantityAwareInterface,
    ParentProductAwareInterface
{
}
