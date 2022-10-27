<?php

namespace Oro\Bundle\PayPalBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether express checkout name does not already used in base integration name.
 */
class UniqueExpressCheckoutName extends Constraint
{
    public string $expressCheckoutNameMessage = 'oro.paypal.express_checkout_name.differs_from_integration_name';
    public string $integrationNameUniquenessMessage = 'oro.paypal.express_checkout_name.unique_integration_name';

    /**
     * {@inheritDoc}
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
