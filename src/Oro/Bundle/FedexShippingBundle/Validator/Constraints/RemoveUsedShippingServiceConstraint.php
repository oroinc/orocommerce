<?php

namespace Oro\Bundle\FedexShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RemoveUsedShippingServiceConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
    {
        return 'oro_fedex_shipping_remove_used_shipping_service_validator';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
