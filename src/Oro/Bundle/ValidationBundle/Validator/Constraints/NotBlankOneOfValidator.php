<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This validator checks that one of fields is required.
 */
class NotBlankOneOfValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotBlankOneOf) {
            throw new UnexpectedTypeException($constraint, NotBlankOneOf::class);
        }

        foreach ($constraint->fields as $fieldGroup) {
            $this->processFieldGroup($value, $fieldGroup, $constraint);
        }
    }

    private function processFieldGroup(mixed $value, array $fieldGroup, NotBlankOneOf $constraint): void
    {
        $fields = array_keys($fieldGroup);
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($fields as $field) {
            $fieldValue = $accessor->getValue($value, $field);
            if (true === $fieldValue || !empty($fieldValue) || '0' == $fieldValue) {
                return;
            }
        }

        foreach ($fields as $field) {
            $this->context
                ->buildViolation($constraint->message, [
                    '%fields%' => implode(', ', array_map(function ($value) {
                        return $this->translator->trans((string)$value);
                    }, $fieldGroup))
                ])
                ->atPath($field)
                ->addViolation();
        }
    }
}
