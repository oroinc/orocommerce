<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator that checks that a primary product unit precision exists in a collection of product unit precisions.
 */
class PrimaryProductUnitPrecisionValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PrimaryProductUnitPrecision) {
            throw new UnexpectedTypeException($constraint, PrimaryProductUnitPrecision::class);
        }

        if (!$value instanceof Product) {
            throw new UnexpectedTypeException($value, Product::class);
        }

        $primaryUnitPrecision = $value->getPrimaryUnitPrecision();
        if (null === $primaryUnitPrecision) {
            return;
        }

        $precisions = $value->getUnitPrecisions();
        if ($precisions instanceof AbstractLazyCollection && !$precisions->isInitialized()) {
            return;
        }

        if (!$this->isPrecisionExistInCollection($primaryUnitPrecision, $precisions)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('unitPrecisions')
                ->addViolation();
        }
    }

    private function isPrecisionExistInCollection(ProductUnitPrecision $precision, Collection $precisions): bool
    {
        $exist = false;
        foreach ($precisions as $item) {
            if ($item->getId() === $precision->getId()) {
                $exist = true;
                break;
            }
        }

        return $exist;
    }
}
