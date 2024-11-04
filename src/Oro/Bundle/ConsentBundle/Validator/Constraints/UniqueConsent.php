<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check for unique values of system config Consent collection.
 */
class UniqueConsent extends Constraint
{
    public string $message = 'oro.consent.validators.consent.unique_consent.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
