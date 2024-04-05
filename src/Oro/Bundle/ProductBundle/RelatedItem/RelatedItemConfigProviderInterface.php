<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

/**
 * Represents a configuration provider for related items, like related products, upsell products, etc.
 */
interface RelatedItemConfigProviderInterface
{
    public function isEnabled(): bool;

    public function getLimit(): int;

    public function isBidirectional(): bool;
}
