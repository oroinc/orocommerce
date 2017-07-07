<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProductUnitExistsValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_product_unit_exists';

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $unit = $value->getUnit();
        $product = $value->getProduct();

        if (!in_array($unit, $product->getAvailableUnitCodes())) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ unit }}', $unit)
                ->setParameter('{{ sku }}', $product->getSku())
                ->addViolation();
        }
    }
}
