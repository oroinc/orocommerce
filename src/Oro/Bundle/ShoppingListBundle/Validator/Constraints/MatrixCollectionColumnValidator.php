<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Math\BigDecimal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks length of the fractional part of the quantity value.
 */
class MatrixCollectionColumnValidator extends ConstraintValidator
{
    /**
     * @param \Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn $value
     * @param Constraint|MatrixCollectionColumn $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value->quantity && null === $value->product) {
            $this->context->buildViolation($constraint->messageOnProductUnavailable)
                ->atPath('quantity')
                ->addViolation();
        }

        if ($value->product) {
            $rootContext = $this->context->getRoot();
            $productUnit = $rootContext->getData()->unit;

            if (!$productUnit instanceof ProductUnit) {
                return null;
            }

            $scale = $value->product->getUnitPrecision($productUnit->getCode());

            if ($scale && $value->quantity) {
                $precision = $scale->getPrecision();

                $fractionPart = strlen(BigDecimal::of($value->quantity)->fraction());
                if ($fractionPart > 0 && $fractionPart > $precision) {
                    $this->context->buildViolation($constraint->messageOnNonValidPrecision)
                        ->setParameter('{{ precision }}', $precision)
                        ->atPath('quantity')
                        ->addViolation();
                }
            }
        }
    }
}
