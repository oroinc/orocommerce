<?php

namespace Oro\Bundle\UPSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating removal of UPS shipping services.
 *
 * This constraint prevents the removal or modification of UPS shipping services that are currently in use by active
 * shipping methods or shipping rules. It ensures data integrity by validating that changes to the UPS transport
 * configuration do not break existing shipping method configurations that depend on specific services.
 */
class RemoveUsedShippingServiceConstraint extends Constraint
{
    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_ups_remove_used_shipping_service_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
