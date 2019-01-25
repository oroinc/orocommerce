<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Brick\Math\Exception\NumberFormatException;
use Oro\Component\Math\BigDecimal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates numeric value including rational numeric values
 */
class NumericRangeValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NumericRange) {
            throw new UnexpectedTypeException($constraint, NumericRange::class);
        }

        if (null === $value) {
            return;
        }

        try {
            $value = BigDecimal::of($value);
        } catch (NumberFormatException $e) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setCode(NumericRange::INVALID_CHARACTERS_ERROR)
                ->addViolation();

            return;
        }

        $max = BigDecimal::of($constraint->max);
        $min = BigDecimal::of($constraint->min);

        if ($value->isGreaterThan($max)) {
            $this->context
                ->buildViolation($constraint->maxMessage)
                ->setParameter('{{ limit }}', (string)$max)
                ->setCode(NumericRange::TOO_HIGH_ERROR)
                ->addViolation();
        } elseif ($value->isLessThan($min)) {
            $this->context
                ->buildViolation($constraint->minMessage)
                ->setParameter('{{ limit }}', (string)$min)
                ->setCode(NumericRange::TOO_LOW_ERROR)
                ->addViolation();
        }
    }
}
