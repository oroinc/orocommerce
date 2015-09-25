<?php

namespace OroB2B\Bundle\AccountBundle\Validator\Constraints;

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
        /** @var ArrayCollection $value */
        /** @var VisibilityChangeSet $constraint */
        if ($value->count() == 0) {
            $this->context->addViolation($constraint->invalidFormatMessage);

            return;
        }
        foreach ($value as $item) {
            if (!isset($item['data']['visibility']) || !isset($item['entity'])) {
                $this->context->addViolation($constraint->invalidFormatMessage);

                return;
            }

            if (!$item['entity'] instanceof $constraint->entityClass) {
                $this->context->addViolation($constraint->invalidDataMessage);

                return;
            }
        }
    }
}
