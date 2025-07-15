<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Set fallback value for product entity fallback fields if they are not set
 */
class ProductEntityFallbackFieldEventListener
{
    private const array FALLBACK_FIELDS = [
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'backOrder',
        'decrementQuantity',
        UpcomingProductProvider::IS_UPCOMING,
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
    ];

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function prePersist(Product $product, LifecycleEventArgs $args): void
    {
        foreach (self::FALLBACK_FIELDS as $fieldName) {
            if (!$this->propertyAccessor->getValue($product, $fieldName)) {
                $fallback = new EntityFieldFallbackValue();
                $fallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
                $this->propertyAccessor->setValue($product, $fieldName, $fallback);
            }
        }
    }
}
