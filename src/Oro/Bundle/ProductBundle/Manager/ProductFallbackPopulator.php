<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Manager;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackFieldProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Populates fallback values for a product.
 */
class ProductFallbackPopulator
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private ProductFallbackFieldProviderInterface $fieldProvider
    ) {
    }

    public function populate(Product $product): bool
    {
        $hasChanges = false;

        foreach ($this->fieldProvider->getFieldsByFallbackId() as $fallbackId => $fields) {
            foreach ($fields as $field) {
                if ($this->ensureFallback($product, $field, $fallbackId)) {
                    $hasChanges = true;
                }
            }
        }

        return $hasChanges;
    }

    private function ensureFallback(Product $product, string $field, string $fallbackId): bool
    {
        if (null !== $this->propertyAccessor->getValue($product, $field)) {
            return false;
        }

        $fallback = new EntityFieldFallbackValue();
        $fallback->setFallback($fallbackId);
        $this->propertyAccessor->setValue($product, $field, $fallback);

        return true;
    }
}
