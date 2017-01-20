<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;

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

            if ($scale) {
                $precision = $scale->getPrecision();

                $intPart = (int)(floor(abs($value->quantity)));
                $fractionPart = abs($value->quantity) - $intPart;

                if ($fractionPart > 0 && strlen(substr(strrchr((string)$fractionPart, '.'), 1)) > $precision) {
                    $this->context->buildViolation($constraint->messageOnNonValidPrecision)
                        ->setParameter('{{ precision }}', $precision)
                        ->atPath('quantity')
                        ->addViolation();
                }
            }
        }
    }
}
