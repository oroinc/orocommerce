<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a product unit precision can be removed from {@see Product::$unitPrecisions} collection.
 */
class ProductUnitPrecisionsCollectionReferencedByProductKitItems extends Constraint
{
    public const UNIT_PRECISION_CANNOT_BE_REMOVED = 'd27238a4-6463-4dfd-b6d9-3f329212fbfb';

    protected static $errorNames = [
        self::UNIT_PRECISION_CANNOT_BE_REMOVED => 'PRODUCT_UNIT_PRECISION_CANNOT_BE_DELETED',
    ];

    public string $message = 'oro.product.unit_precisions_items.referenced_by_product_kits';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
