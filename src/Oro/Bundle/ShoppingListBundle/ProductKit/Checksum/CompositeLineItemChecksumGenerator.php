<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

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

    public function getChecksum(LineItem $lineItem): ?string
    {
        foreach ($this->checksumGenerators as $checksumGenerator) {
            if (($checksum = $checksumGenerator->getChecksum($lineItem)) !== null) {
                return $checksum;
            }
        }

        return null;
    }
}
