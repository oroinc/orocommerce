<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

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

    public function getChecksum(LineItem $lineItem): ?string
    {
        $checksum = $this->innerChecksumGenerator->getChecksum($lineItem);
        if ($checksum !== null) {
            return sha1($checksum);
        }

        return null;
    }
}
