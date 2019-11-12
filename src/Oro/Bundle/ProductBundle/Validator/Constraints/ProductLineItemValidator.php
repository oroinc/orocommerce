<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that product unit can be set for given product.
 */
class ProductLineItemValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ProductLineItem) {
            throw new UnexpectedTypeException($constraint, ProductLineItem::class);
        }

        /** @var ProductLineItemInterface $value */
        $unit = $value->getProductUnit();
        if (null === $unit) {
            return;
        }

        $product = $value->getProduct();
        if (null === $product) {
            return;
        }

        $unitCode = $unit->getCode();
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            $productUnit = $unitPrecision->getUnit();
            if (null === $productUnit) {
                continue;
            }

            if ($productUnit->getCode() === $unitCode) {
                if ($constraint->sell && !$unitPrecision->isSell()) {
                    continue;
                }

                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->atPath($constraint->path)
            ->addViolation();
    }
}
