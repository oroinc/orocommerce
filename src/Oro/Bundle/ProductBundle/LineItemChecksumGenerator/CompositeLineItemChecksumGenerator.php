<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Line item checksum generator that delegates calls to the inner generators.
 */
class CompositeLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    /**
     * @var iterable|LineItemChecksumGeneratorInterface[]
     */
    private iterable $checksumGenerators;

    /**
     * @param iterable<LineItemChecksumGeneratorInterface> $checksumGenerators
     */
    public function __construct(iterable $checksumGenerators)
    {
        $this->checksumGenerators = $checksumGenerators;
    }

    #[\Override]
    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        foreach ($this->checksumGenerators as $checksumGenerator) {
            if (($checksum = $checksumGenerator->getChecksum($lineItem)) !== null) {
                return $checksum;
            }
        }

        return null;
    }
}
