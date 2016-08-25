<?php

namespace Oro\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;

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
                    'Oro\Bundle\TaxBundle\Entity\ZipCode',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        if ($entity->getZipCode() && ($entity->getZipRangeStart() || $entity->getZipRangeEnd())) {
            $context->buildViolation($constraint->onlyOneTypeMessage)->atPath('zipCode')->addViolation();
        }

        if (!$entity->getZipRangeStart() && $entity->getZipRangeEnd()) {
            $context->buildViolation($constraint->rangeShouldHaveBothFieldMessage)
                ->atPath('zipRangeStart')
                ->addViolation();
        }

        if ($entity->getZipRangeStart() && !$entity->getZipRangeEnd()) {
            $context->buildViolation($constraint->rangeShouldHaveBothFieldMessage)
                ->atPath('zipRangeEnd')
                ->addViolation();
        }

        if ($entity->getZipRangeStart() && $entity->getZipRangeEnd() && (
            !$this->isInteger($entity->getZipRangeStart()) ||
            !$this->isInteger($entity->getZipRangeEnd()))
        ) {
            $context->buildViolation($constraint->onlyNumericRangesSupported)->atPath('zipRangeStart')->addViolation();
        }

        if (!$entity->getZipCode() && !$entity->getZipRangeStart() && !$entity->getZipRangeEnd()) {
            $context->buildViolation($constraint->zipCodeCanNotBeEmpty)->atPath('zipRangeStart')->addViolation();
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    protected function isInteger($value)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        $violations = $context->getValidator()->validate($value, new Integer());

        return $violations->count() === 0;
    }
}
