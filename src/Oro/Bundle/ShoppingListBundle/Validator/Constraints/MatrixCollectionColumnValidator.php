<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
            $this->context->buildViolation($constraint->message)
                ->atPath('quantity')
                ->addViolation();
        }
    }
}
