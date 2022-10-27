<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate that a given value is a valid decimal including checking of formatted values.
 */
class DecimalValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->validationShouldBeSkipped($value)) {
            return;
        }

        if (!is_scalar($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }
        // Remove trailing zeroes if value is numeric to validate value correctly. "49.0100000" will fail
        if (is_numeric($value) && is_string($value) && str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);

        $position = 0;
        $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);

        if (intl_is_failure($formatter->getErrorCode()) || $position < strlen($value)) {
            /** @var Decimal $constraint */
            $this->context->addViolation($constraint->message, ['{{ value }}' => $this->formatValue($value)]);
        }
    }

    protected function validationShouldBeSkipped($value): bool
    {
        return null === $value || '' === $value || is_float($value);
    }
}
