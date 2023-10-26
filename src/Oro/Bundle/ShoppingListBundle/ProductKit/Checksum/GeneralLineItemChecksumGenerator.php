<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Line item checksum generator that creates a checksum for general line items.
 *
 * @deprecated since 5.1, use
 *  {@see \Oro\Bundle\ProductBundle\LineItemChecksumGenerator\GeneralLineItemChecksumGenerator} instead.
 */
class GeneralLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    public function getChecksum(LineItem $lineItem): ?string
    {
        if (!$lineItem->getUnit() || !$lineItem->getProduct() || $lineItem->getProduct()->isKit()) {
            // product-kit line item is not supported.
            return null;
        }

        return sprintf(
            "%s|%s",
            $lineItem->getProduct()->getId(),
            $lineItem->getUnit()->getCode()
        );
    }
}
