<?php

namespace OroB2B\Bundle\AccountBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Doctrine\Common\Collections\ArrayCollection;

class VisibilityChangeSetValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ArrayCollection $value */
        if ($value->count() == 0) {

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
