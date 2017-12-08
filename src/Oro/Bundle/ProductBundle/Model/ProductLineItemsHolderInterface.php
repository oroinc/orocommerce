<?php

namespace Oro\Bundle\ProductBundle\Model;

interface ProductLineItemsHolderInterface
{
    /**
     * @return ProductLineItemInterface[]
     */
    public function getLineItems();
}
