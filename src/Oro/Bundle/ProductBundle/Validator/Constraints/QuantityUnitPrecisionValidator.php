<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class QuantityUnitPrecisionValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_quantity_unit_precision';

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $quantity = $value->getQuantity();
        $product = $value->getProduct();
        $unit = $value->getUnit();

        if (round($quantity, $product->getUnitPrecision($unit)->getPrecision()) != $quantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ unit }}', $unit)
                ->addViolation();
        }
    }
}
