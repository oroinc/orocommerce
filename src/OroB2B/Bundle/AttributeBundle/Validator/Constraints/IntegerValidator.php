<?php

namespace OroB2B\Bundle\AttributeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IntegerValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_DOWN);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 0);

        $decimalSeparator = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        $position = 0;
        $formatter->parse($value, PHP_INT_SIZE == 8 ? $formatter::TYPE_INT64 : $formatter::TYPE_INT32, $position);

        if (intl_is_failure($formatter->getErrorCode())
            || strpos($value, $decimalSeparator) !== false
            || $position < strlen($value)
        ) {
            /** @var Integer $constraint */
            $this->context->addViolation($constraint->message, ['{{ value }}' => $this->formatValue($value)]);
        }
    }
}
