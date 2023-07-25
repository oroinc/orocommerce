<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Interface for product kit item line items.
 */
interface ProductKitItemLineItemInterface extends ProductLineItemInterface, ProductKitItemAwareInterface
{
    public function getLineItem(): ?ProductLineItemInterface;
}
