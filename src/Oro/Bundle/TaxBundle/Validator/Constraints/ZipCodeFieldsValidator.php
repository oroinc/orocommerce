<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is used to check tax jurisdiction zip codes.
 */
class ZipCodeFieldsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ZipCodeFields) {
            throw new UnexpectedTypeException($constraint, ZipCodeFields::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ZipCode) {
            throw new UnexpectedTypeException($value, ZipCode::class);
        }

        if ($value->getZipCode() && ($value->getZipRangeStart() || $value->getZipRangeEnd())) {
            $this->context->buildViolation($constraint->onlyOneTypeMessage)
                ->atPath('zipCode')
                ->addViolation();
        }

        if (!$value->getZipRangeStart() && $value->getZipRangeEnd()) {
            $this->context->buildViolation($constraint->rangeShouldHaveBothFieldMessage)
                ->atPath('zipRangeStart')
                ->addViolation();
        }

        if ($value->getZipRangeStart() && !$value->getZipRangeEnd()) {
            $this->context->buildViolation($constraint->rangeShouldHaveBothFieldMessage)
                ->atPath('zipRangeEnd')
                ->addViolation();
        }

        if ($value->getZipRangeStart()
            && $value->getZipRangeEnd()
            && (!$this->isInteger($value->getZipRangeStart()) || !$this->isInteger($value->getZipRangeEnd()))
        ) {
            $this->context->buildViolation($constraint->onlyNumericRangesSupported)
                ->atPath('zipRangeStart')
                ->addViolation();
        }

        if (!$value->getZipCode() && !$value->getZipRangeStart() && !$value->getZipRangeEnd()) {
            $this->context->buildViolation($constraint->zipCodeCanNotBeEmpty)
                ->atPath('zipRangeStart')
                ->addViolation();
        }
    }

    protected function isInteger(mixed $value): bool
    {
        return $this->context->getValidator()->validate($value, new Integer())->count() === 0;
    }
}
