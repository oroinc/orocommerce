<?php

namespace Oro\Bundle\CustomerBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ScopeWithCustomerGroupAndCustomerValidator extends ConstraintValidator
{
    /**
     * @param ScopeWithCustomerGroupAndCustomer $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Collection || $value->isEmpty()) {
            return;
        }

        foreach ($value->getValues() as $index => $scope) {
            /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
            if ($scope->getCustomer() && $scope->getCustomerGroup()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath("[$index]")
                    ->addViolation();
            }
        }
    }
}
