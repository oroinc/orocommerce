<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that quick add component processor exists and is allowed.
 */
class QuickAddComponentProcessorValidator extends ConstraintValidator
{
    private ComponentProcessorRegistry $componentProcessorRegistry;

    public function __construct(ComponentProcessorRegistry $componentProcessorRegistry)
    {
        $this->componentProcessorRegistry = $componentProcessorRegistry;
    }

    /**
     * @param QuickAddComponentProcessor $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!is_scalar($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!$constraint instanceof QuickAddComponentProcessor) {
            throw new UnexpectedTypeException($constraint, QuickAddComponentProcessor::class);
        }

        if (!$this->componentProcessorRegistry->hasProcessor($value)
            || !$this->componentProcessorRegistry->getProcessorByName($value)->isAllowed()) {
            $this->context
                ->buildViolation($constraint->message, ['{{ name }}' => $value])
                ->setCode($constraint::NOT_AVAILABLE_PROCESSOR)
                ->addViolation();
        }
    }
}
