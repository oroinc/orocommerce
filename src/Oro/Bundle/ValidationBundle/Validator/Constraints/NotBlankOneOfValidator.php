<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NotBlankOneOfValidator extends ConstraintValidator
{
    const ALIAS = 'oro_validation_not_blank_one_of';

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param PropertyAccessor $accessor
     */
    public function __construct(PropertyAccessor $accessor)
    {
        $this->accessor = $accessor;
    }

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
     * @param TranslatorInterface $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param object|array $value
     * @param array $fieldGroup
     * @param NotBlankOneOf $constraint
     */
    protected function processFieldGroup($value, array $fieldGroup, NotBlankOneOf $constraint)
    {
        $fields = array_keys($fieldGroup);
        foreach ($fields as $field) {
            if (null !== $this->accessor->getValue($value, $field)) {
                return;
            }
        }

        $labels = $fieldGroup;
        if ($this->translator) {
            $labels = array_map(
                function ($label) {
                    return $this->translator->trans($label);
                },
                $fieldGroup
            );
        }

        foreach ($fields as $field) {
            /** @var ExecutionContextInterface $context */
            $context = $this->context;
            $context->buildViolation(
                $constraint->message,
                [
                    "%fields%" => join(', ', $labels),
                ]
            )
                ->atPath($field)
                ->addViolation();
        }
    }
}
