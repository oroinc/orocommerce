<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating the ship-until date during checkout.
 *
 * This constraint is applied at the class level to validate that the ship-until date
 * for checkout line items is valid according to inventory and upcoming product settings.
 */
class CheckoutShipUntil extends Constraint
{
    public $message = 'oro.inventory.checkout.validators.ship_until';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
