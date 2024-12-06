<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * The line item checksum generator that delegates calls to the inner generators.
 */
class CompositeLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    /**
     * @param iterable<LineItemChecksumGeneratorInterface> $checksumGenerators
     */
    public function __construct(
        private readonly iterable $checksumGenerators
    ) {
    }

    #[\Override]
    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        foreach ($this->checksumGenerators as $checksumGenerator) {
            $checksum = $checksumGenerator->getChecksum($lineItem);
            if (null !== $checksum) {
                return $checksum;
            }
        }

        return null;
    }
}
