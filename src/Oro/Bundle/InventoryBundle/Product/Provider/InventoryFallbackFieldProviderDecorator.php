<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Product\Provider;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackFieldProviderInterface;

/**
 * Extends product fallback fields with inventory-specific fields.
 */
class InventoryFallbackFieldProviderDecorator implements ProductFallbackFieldProviderInterface
{
    private const CATEGORY_FALLBACK_FIELDS = [
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'backOrder',
        'decrementQuantity',
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        UpcomingProductProvider::IS_UPCOMING,
    ];

    public function __construct(
        private ProductFallbackFieldProviderInterface $innerProvider
    ) {
    }

    #[\Override]
    public function getFieldsByFallbackId(): array
    {
        return [
            ...$this->innerProvider->getFieldsByFallbackId(),
            CategoryFallbackProvider::FALLBACK_ID => self::CATEGORY_FALLBACK_FIELDS,
        ];
    }
}
