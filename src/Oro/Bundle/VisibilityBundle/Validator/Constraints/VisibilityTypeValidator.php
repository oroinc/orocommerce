<?php

namespace Oro\Bundle\VisibilityBundle\Validator\Constraints;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a visibility level is applicable for a specific visibility rule.
 */
class VisibilityTypeValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof VisibilityType) {
            throw new UnexpectedTypeException($constraint, VisibilityType::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof VisibilityInterface) {
            throw new UnexpectedTypeException($value, VisibilityInterface::class);
        }

        $targetEntity = $value->getTargetEntity();
        if (null === $targetEntity) {
            return;
        }

        $availableTypes = $value::getVisibilityList($targetEntity);
        if (!\in_array($value->getVisibility(), $availableTypes, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ available_types }}', implode(', ', $availableTypes))
                ->atPath($constraint->path)
                ->addViolation();
        }
    }
}
