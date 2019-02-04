<?php

namespace Oro\Bundle\PayPalBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * PayPalSettings express checkout mame constraint.
 */
class UniqueExpressCheckoutName extends Constraint
{
    /**
     * @var string
     */
    public $expressCheckoutNameMessage = 'oro.paypal.express_checkout_name.differs_from_integration_name';

    /**
     * @var string
     */
    public $integrationNameUniquenessMessage = 'oro.paypal.express_checkout_name.unique_integration_name';

    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
    {
        return UniqueExpressCheckoutNameValidator::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
