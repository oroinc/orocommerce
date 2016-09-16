<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NotBlankOneOfValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @param NotBlankOneOf $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        foreach ($constraint->fields as $fieldGroup) {
            $this->processFieldGroup($value, $fieldGroup, $constraint);
        }
    }

    /**
     * @param object|array $value
     * @param array $fieldGroup
     * @param NotBlankOneOf $constraint
     */
    protected function processFieldGroup($value, array $fieldGroup, NotBlankOneOf $constraint)
    {
        $fields = array_keys($fieldGroup);
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($fields as $field) {
            if (null !== $accessor->getValue($value, $field)) {
                return;
            }
        }

        foreach ($fields as $field) {
            /** @var ExecutionContextInterface $context */
            $context = $this->context;
            $context->buildViolation(
                $constraint->message,
                [
                    "%fields%" => join(', ', $fieldGroup),
                ]
            )
                ->atPath($field)
                ->addViolation();
        }
    }
}
