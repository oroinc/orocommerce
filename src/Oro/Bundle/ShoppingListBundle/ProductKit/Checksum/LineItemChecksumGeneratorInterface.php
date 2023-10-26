<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Interface for {@see LineItem} checksum generator.
 *
 * @deprecated since 5.1, use
 *  {@see \Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface} instead.
 */
interface LineItemChecksumGeneratorInterface
{
    public function getChecksum(LineItem $lineItem): ?string;
}
