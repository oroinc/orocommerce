<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This validator checks that one of fields should be blank.
 */
class BlankOneOfValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof BlankOneOf) {
            throw new UnexpectedTypeException($constraint, BlankOneOf::class);
        }

        foreach ($constraint->fields as $fieldGroup) {
            if (!$this->validateFieldGroup($value, $fieldGroup)) {
                $this->addViolation($fieldGroup, $constraint);
            }
        }
    }

    private function validateFieldGroup(mixed $value, array $fieldGroup): bool
    {
        $fieldNames = array_keys($fieldGroup);
        foreach ($fieldNames as $fieldName) {
            $fieldValue = $this->propertyAccessor->getValue($value, $fieldName);
            if (false === $fieldValue || (empty($fieldValue) && '0' != $fieldValue)) {
                return true;
            }
        }

        return false;
    }

    private function addViolation(array $fieldGroup, BlankOneOf $constraint): void
    {
        $fieldNames = array_keys($fieldGroup);
        $this->context
            ->buildViolation($constraint->message, [
                '%fields%' => implode(', ', array_map(function ($value) {
                    return $this->translator->trans((string) $value);
                }, $fieldGroup))
            ])
            ->atPath($fieldNames[0])
            ->addViolation();
    }
}
