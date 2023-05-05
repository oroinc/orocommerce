<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that product unit is within the intersection of products units of the {@see ProductKitItem} products.
 */
class ProductKitItemUnitAvailableForSpecifiedProductsValidator extends ConstraintValidator
{
    private ProductKitItemProductUnitChecker $productUnitChecker;

    public function __construct(ProductKitItemProductUnitChecker $productUnitChecker)
    {
        $this->productUnitChecker = $productUnitChecker;
    }

    /**
     * @param ProductKitItem|null $value
     * @param ProductKitItemUnitAvailableForSpecifiedProducts $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemUnitAvailableForSpecifiedProducts) {
            throw new UnexpectedTypeException($constraint, ProductKitItemUnitAvailableForSpecifiedProducts::class);
        }

        if (!$value instanceof ProductKitItem) {
            throw new UnexpectedValueException($value, ProductKitItem::class);
        }

        $productKitItemUnit = $value->getProductUnit();
        if ($productKitItemUnit === null) {
            return;
        }

        $products = $value->getProducts();

        $conflictingProducts = $this->productUnitChecker->getConflictingProducts($productKitItemUnit, $products);
        if ($conflictingProducts) {
            $this->context->buildViolation(
                $constraint->message,
                [
                    '%unit_code%' => $this->formatValue($productKitItemUnit->getCode()),
                    '%skus%' => $this->formatValues(
                        array_map(static fn (Product $product) => $product->getSkuUppercase(), $conflictingProducts)
                    )
                ]
            )
                ->atPath('productUnit')
                ->setCode(ProductKitItemUnitAvailableForSpecifiedProducts::PRODUCT_UNIT_NOT_ALLOWED)
                ->addViolation();
        }
    }
}
