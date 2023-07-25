<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Describes a ProductKitItem-aware instance.
 */
interface ProductKitItemAwareInterface
{
    public function getKitItem(): ?ProductKitItem;

    public function getSortOrder(): int;
}
