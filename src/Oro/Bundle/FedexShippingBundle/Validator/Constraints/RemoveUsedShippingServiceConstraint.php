<?php

namespace Oro\Bundle\FedexShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating removal of FedEx shipping services.
 *
 * This constraint prevents removal of FedEx shipping services that are currently
 * in use, ensuring data integrity and preventing broken shipping method configurations.
 */
class RemoveUsedShippingServiceConstraint extends Constraint
{
    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_fedex_shipping_remove_used_shipping_service_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
