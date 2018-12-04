<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates for unique values of system config Consent collection
 */
class UniqueConsent extends Constraint
{
    /** @var string */
    public $message = 'oro.consent.validators.consent.unique_consent.message';

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
