<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all landing pages that uses in checked consents exist in the database
 */
class RemovedLandingPages extends Constraint
{
    public string $message = 'oro.consent.validators.consent.removed_landing_pages.message';
}
