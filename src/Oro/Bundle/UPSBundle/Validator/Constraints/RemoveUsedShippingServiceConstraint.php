<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return 'oro_ups_remove_used_shipping_service_validator';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
