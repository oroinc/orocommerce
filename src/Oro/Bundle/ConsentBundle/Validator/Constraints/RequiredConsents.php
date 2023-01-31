<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all required constraints are checked
 */
class RequiredConsents extends Constraint
{
    public string $message = 'oro.consent.validators.consent.required_consents.message';
}
