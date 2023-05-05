<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a shipping rule can be enabled.
 */
class ShippingRuleEnable extends Constraint
{
    public string $message = 'oro.shipping.shippingrule.enabled.message';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
