<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Interface for line item aware of its checksum.
 */
interface ProductLineItemChecksumAwareInterface
{
    public function getChecksum(): string;
}
