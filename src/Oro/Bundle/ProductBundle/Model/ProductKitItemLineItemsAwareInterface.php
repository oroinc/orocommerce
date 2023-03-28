<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for line item aware of product kit item line items.
 */
interface ProductKitItemLineItemsAwareInterface
{
    /**
     * @return Collection<ProductKitItemLineItemInterface>
     */
    public function getKitItemLineItems();

    public function getChecksum(): string;
}
