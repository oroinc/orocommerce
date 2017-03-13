<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingService extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return RemoveUsedShippingServiceValidator::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
