<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all checked consents exist in the database
 */
class RemovedConsents extends Constraint
{
    /** @var string */
    public $message = 'oro.consent.validators.consent.removed_consents.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_consent_removed_consents_validator';
    }
}
