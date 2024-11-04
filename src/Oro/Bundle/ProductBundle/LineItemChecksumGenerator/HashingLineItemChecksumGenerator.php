<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Line item checksum generator that makes a hash from the checksum coming from the inner generator.
 */
class HashingLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    private LineItemChecksumGeneratorInterface $innerChecksumGenerator;

    public function __construct(LineItemChecksumGeneratorInterface $innerChecksumGenerator)
    {
        $this->innerChecksumGenerator = $innerChecksumGenerator;
    }

    #[\Override]
    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        $checksum = $this->innerChecksumGenerator->getChecksum($lineItem);
        if ($checksum !== null) {
            return sha1($checksum);
        }

        return null;
    }
}
