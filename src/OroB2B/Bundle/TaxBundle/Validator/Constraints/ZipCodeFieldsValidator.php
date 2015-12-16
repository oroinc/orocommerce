<?php

namespace OroB2B\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ZipCodeFieldsValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_tax_zip_code_fields';

    /**
     * {@inheritdoc}
     * @param ZipCodeFields $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        $propertyPath = $this->context->getPropertyPath() . '.zipCodes';

        if ($entity->getZipCode() && ($entity->getZipRangeStart() || $entity->getZipRangeEnd())) {
            $this->context->addViolationAt($propertyPath, $constraint->onlyOneTypeMessage);
        }

        if ($entity->getZipRangeStart() xor $entity->getZipRangeEnd()) {
            $this->context->addViolationAt($propertyPath, $constraint->rangeBothFieldMessage);
        }
    }
}
