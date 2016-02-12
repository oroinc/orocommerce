<?php

namespace OroB2B\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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

        if (!$entity->getZipCode() && !$entity->getZipRangeStart() && !$entity->getZipRangeEnd()) {
            $this->context->addViolationAt('zipRangeStart', $constraint->zipCodeCanNotBeEmpty);
        }
    }
}
