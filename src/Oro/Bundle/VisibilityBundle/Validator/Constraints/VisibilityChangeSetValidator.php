<?php

namespace Oro\Bundle\VisibilityBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that each element of a visibility change set
 * is assigned to an entity of a specific type.
 */
class VisibilityChangeSetValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof VisibilityChangeSet) {
            throw new UnexpectedTypeException($constraint, VisibilityChangeSet::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ArrayCollection) {
            throw new UnexpectedTypeException($value, ArrayCollection::class);
        }

        if ($value->isEmpty()) {
            return;
        }

        foreach ($value as $item) {
            $entity = $item['entity'] ?? null;
            if (!$entity instanceof $constraint->entityClass) {
                $this->context->addViolation($constraint->message);
                break;
            }
        }
    }
}
