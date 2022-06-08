<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks if a product unit precision can be removed from {@see Product::$unitPrecisions} collection.
 */
class ProductUnitPrecisionsCollectionReferencedByProductKitItemsValidator extends ConstraintValidator
{
    private ProductKitsByUnitPrecisionProvider $productKitsByUnitPrecisionProvider;

    public function __construct(ProductKitsByUnitPrecisionProvider $productKitsByUnitPrecisionProvider)
    {
        $this->productKitsByUnitPrecisionProvider = $productKitsByUnitPrecisionProvider;
    }

    /**
     * @param Product|null $value
     * @param ProductUnitPrecisionsCollectionReferencedByProductKitItems $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductUnitPrecisionsCollectionReferencedByProductKitItems) {
            throw new UnexpectedTypeException(
                $constraint,
                ProductUnitPrecisionsCollectionReferencedByProductKitItems::class
            );
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        $unitPrecisions = $value->getUnitPrecisions();
        if (!$unitPrecisions instanceof PersistentCollection || !$unitPrecisions->isDirty()) {
            // Skips further execution as the product unit precision collection is not changed.
            return;
        }

        /** @var ProductUnitPrecision $deletedUnitPrecision */
        foreach ($unitPrecisions->getDeleteDiff() as $deletedUnitPrecision) {
            $productsSkus = $this->productKitsByUnitPrecisionProvider->getRelatedProductKitsSku($deletedUnitPrecision);
            if (!$productsSkus) {
                // Skips further execution as the product unit precision is not referenced by product kit items.
                continue;
            }

            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ product_unit }}', $deletedUnitPrecision->getProductUnitCode())
                ->setParameter('{{ product_kits_skus }}', implode(', ', $productsSkus))
                ->atPath('unitPrecisions.' . array_search($deletedUnitPrecision, $unitPrecisions->getSnapshot(), true))
                ->setCode(ProductUnitPrecisionsCollectionReferencedByProductKitItems::UNIT_PRECISION_CANNOT_BE_REMOVED)
                ->addViolation();
        }
    }
}
