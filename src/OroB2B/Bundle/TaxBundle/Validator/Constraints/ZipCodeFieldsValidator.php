<?php

namespace OroB2B\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;

class ZipCodeFieldsValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_tax_zip_code_fields';

    /**
     * {@inheritdoc}
     * @param ZipCodeFields $constraint
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof ZipCode) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    'OroB2B\Bundle\TaxBundle\Entity\ZipCode',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        if ($entity->getZipCode() && ($entity->getZipRangeStart() || $entity->getZipRangeEnd())) {
            $this->context->addViolationAt('zipCode', $constraint->onlyOneTypeMessage);
        }

        if (!$entity->getZipRangeStart() && $entity->getZipRangeEnd()) {
            $this->context->addViolationAt('zipRangeStart', $constraint->rangeShouldHaveBothFieldMessage);
        }

        if ($entity->getZipRangeStart() && !$entity->getZipRangeEnd()) {
            $this->context->addViolationAt('zipRangeEnd', $constraint->rangeShouldHaveBothFieldMessage);
        }

        if ($entity->getZipRangeStart() && $entity->getZipRangeEnd() && (
            !$this->validateInteger($entity->getZipRangeStart()) ||
            !$this->validateInteger($entity->getZipRangeEnd()))
        ) {
            $this->context->addViolationAt('zipRangeStart', $constraint->onlyNumericRangesSupported);
        }
    }

    /**
     * It's a part of Integer validator
     *
     * @param string $value
     * @return bool
     *
     * @see OroB2B\Bundle\ValidationBundle\Validator\Constraints\IntegerValidator
     */
    protected function validateInteger($value)
    {
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

        return !intl_is_failure($formatter->getErrorCode())
        && strpos($value, $decimalSeparator) === false
        && $position === strlen($value);

    }
}
