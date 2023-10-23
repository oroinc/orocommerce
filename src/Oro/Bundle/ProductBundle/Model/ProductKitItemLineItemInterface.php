<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Interface for product kit item line items.
 */
interface ProductKitItemLineItemInterface extends ProductLineItemInterface, ProductKitItemAwareInterface
{
    public function getLineItem(): ?ProductLineItemInterface;

    /**
     * @deprecated since 5.1, will be moved to ProductKitItemAwareInterface.
     */
    public function getKitItem(): ?ProductKitItem;

    /**
     * @deprecated since 5.1, will be moved to ProductKitItemAwareInterface.
     */
    public function getSortOrder(): int;
}
