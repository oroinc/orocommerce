<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator checks for unique values of system config Consent collection.
 */
class UniqueConsentValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueConsent) {
            throw new UnexpectedTypeException($constraint, UniqueConsent::class);
        }

        if (!\is_array($value)) {
            return;
        }

        $ids = [];
        foreach ($value as $index => $item) {
            $id = $item[ConsentConfigConverter::CONSENT_KEY];
            if (null === $id) {
                continue;
            }
            if (\in_array($id, $ids, true)) {
                $this->context->buildViolation($constraint->message, [])
                    ->atPath(sprintf('[%d].%s', $index, ConsentConfigConverter::CONSENT_KEY))
                    ->addViolation();
            }
            $ids[] = $id;
        }
    }
}
