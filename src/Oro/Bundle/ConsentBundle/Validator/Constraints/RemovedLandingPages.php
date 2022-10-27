<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all landing pages that uses in checked consents exist in the database
 */
class RemovedLandingPages extends Constraint
{
    /** @var string */
    public $message = 'oro.consent.validators.consent.removed_landing_pages.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_consent_removed_landing_pages_validator';
    }
}
