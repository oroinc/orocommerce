<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that all required constraints are checked
 */
class RequiredConsents extends Constraint
{
    /** @var string */
    public $message = 'oro.consent.validators.consent.required_consents.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_consent.validator.required_consents';
    }
}
