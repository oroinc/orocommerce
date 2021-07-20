<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlankOneOfValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(TranslatorInterface $translator, PropertyAccessor $propertyAccessor)
    {
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     *
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        foreach ($constraint->fields as $fieldGroup) {
            if (!$this->validateFieldGroup($value, $fieldGroup)) {
                $this->addViolation($fieldGroup, $constraint);
            }
        }
    }

    /**
     * @param mixed    $value
     * @param string[] $fieldGroup
     *
     * @return bool
     *
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    private function validateFieldGroup($value, array $fieldGroup)
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

    private function addViolation(array $fieldGroup, Constraint $constraint)
    {
        $fieldsTranslation = implode(', ', array_map(function ($value) {
            return $this->translator->trans($value);
        }, $fieldGroup));

        $fieldNames = array_keys($fieldGroup);
        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        $context
            ->buildViolation(
                $constraint->message,
                [
                    '%fields%' => $fieldsTranslation
                ]
            )
            ->atPath($fieldNames[0])
            ->addViolation();
    }
}
