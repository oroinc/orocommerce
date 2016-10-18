<?php

namespace Oro\Bundle\CustomerBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class VisibilityChangeSetValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ArrayCollection) {
            return;
        }

        if (!$value->count()) {
            return;
        }

        foreach ($value as $item) {
            if (isset($item['entity']) && !$item['entity'] instanceof $constraint->entityClass) {
                /** @var VisibilityChangeSet $constraint */
                $this->context->addViolation($constraint->invalidDataMessage);

                return;
            }
        }
    }
}
