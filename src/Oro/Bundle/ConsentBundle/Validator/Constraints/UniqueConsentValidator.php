<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * Validates for unique values of system config Consent collection
 */
class UniqueConsentValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param ConsentConfig[] $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ExecutionContext $context */
        $context = $this->context;

        $ids = [];
        foreach ($value as $index => $item) {
            if (null === $id = $item[$this->getConsentFieldName()]) {
                continue;
            }
            if (in_array($id, $ids, true)) {
                $context->buildViolation($constraint->message, [])
                    ->atPath(sprintf('[%d].%s', $index, $this->getConsentFieldName()))
                    ->addViolation();
            }
            $ids[] = $id;
        }
    }

    /**
     * @return string
     */
    protected function getConsentFieldName()
    {
        return ConsentConfigConverter::CONSENT_KEY;
    }
}
