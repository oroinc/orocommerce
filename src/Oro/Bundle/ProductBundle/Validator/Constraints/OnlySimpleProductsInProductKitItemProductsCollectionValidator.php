<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that only product with type {@see \Oro\Bundle\ProductBundle\Entity\Product::TYPE_SIMPLE}
 * can be used in kit options.
 */
class OnlySimpleProductsInProductKitItemProductsCollectionValidator extends ConstraintValidator
{
    /**
     * @param ProductKitItem|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof OnlySimpleProductsInProductKitItemProductsCollection) {
            throw new UnexpectedTypeException($constraint, OnlySimpleProductsInProductKitItemProductsCollection::class);
        }

        if (!$value instanceof ProductKitItem) {
            throw new UnexpectedValueException($value, ProductKitItem::class);
        }

        foreach ($value->getProducts() as $index => $product) {
            if ($product->getType() !== Product::TYPE_SIMPLE) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('kitItemProducts.' . $index)
                    ->addViolation();
            }
        }
    }
}
