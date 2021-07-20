<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Regex validator for SKU
 */
class SkuRegexValidator extends ConstraintValidator
{
    /**
     * @var string
     */
    private $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof SkuRegex) {
            throw new UnexpectedTypeException($constraint, SkuRegex::class);
        }

        if (!$value) {
            return;
        }

        $validator = $this->context->getValidator();
        $violations = $validator->validate(
            $value,
            new Regex(['pattern' => $this->pattern])
        );

        if ($violations->count()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
