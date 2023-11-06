<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Interface for {@see ProductLineItemInterface} checksum generator.
 */
interface LineItemChecksumGeneratorInterface
{
    public function getChecksum(ProductLineItemInterface $lineItem): ?string;
}
