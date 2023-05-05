<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Interface for product kit item line items.
 */
interface ProductKitItemLineItemInterface extends ProductLineItemInterface
{
    public function getLineItem(): ?ProductLineItemInterface;

    public function getKitItem(): ?ProductKitItem;

    public function getSortOrder(): int;
}
