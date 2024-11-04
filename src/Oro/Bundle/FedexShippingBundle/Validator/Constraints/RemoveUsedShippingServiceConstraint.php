<?php

namespace Oro\Bundle\FedexShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
