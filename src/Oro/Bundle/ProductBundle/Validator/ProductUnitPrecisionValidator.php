<?php

namespace Oro\Bundle\ProductBundle\Validator;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionConstraint;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_unit_precision_validator';

    /** @var ExecutionContextInterface */
    protected $context;

    /**
     * @param ProductUnitPrecision[]|Collection $value
     * @param ProductUnitPrecisionConstraint|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $existingCodes = [];
        foreach ($value as $unitPrecision) {
            $unitCode = $unitPrecision->getProductUnitCode();
            if (in_array($unitCode, $existingCodes, true)) {
                $this->context->addViolation($constraint->message);
                return;
            }
            $existingCodes[] = $unitCode;
        }
    }
}
