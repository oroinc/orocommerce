<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CheckoutShipUntil extends Constraint
{
    public $message = 'oro.inventory.checkout.validators.ship_until';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
