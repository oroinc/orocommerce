<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider;

/**
 * Interface for providers that define product fallback fields grouped by their fallback IDs.
 */
interface ProductFallbackFieldProviderInterface
{
    /**
     * Returns fields grouped by fallback ID
     *
     * Example:
     * [
     *     'themeConfiguration' => ['pageTemplate'],
     *     'category' => ['inventoryThreshold', 'backOrder', 'manageInventory'],
     * ]
     *
     * @return array<string, string[]>
     */
    public function getFieldsByFallbackId(): array;
}
