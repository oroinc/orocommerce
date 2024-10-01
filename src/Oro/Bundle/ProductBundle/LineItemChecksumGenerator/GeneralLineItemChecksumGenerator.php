<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * General line item checksum generator.
 */
class GeneralLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    #[\Override]
    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        if (!$lineItem->getProductUnit() || !$lineItem->getProduct()) {
            return null;
        }

        return sprintf(
            '%s|%s',
            $lineItem->getProduct()->getId(),
            $lineItem->getProductUnit()?->getCode() ?? $lineItem->getProductUnitCode()
        );
    }
}
