<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
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
        foreach ($constraint->fields as $field) {
            if (null !== $this->accessor->getValue($value, $field)) {
                return;
            }
        }

        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context->buildViolation($constraint->message)->addViolation();
    }
}
