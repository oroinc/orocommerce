<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all checked consents exist in the database
 */
class RemovedConsents extends Constraint
{
    public string $message = 'oro.consent.validators.consent.removed_consents.message';
}
