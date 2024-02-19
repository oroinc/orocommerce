<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for line item aware of product kit item line items.
 */
interface ProductKitItemLineItemsAwareInterface
{
    /**
     * @return Product|null
     */
    public function getProduct();

    /**
     * @return Collection<ProductKitItemLineItemInterface>
     */
    public function getKitItemLineItems();
}
