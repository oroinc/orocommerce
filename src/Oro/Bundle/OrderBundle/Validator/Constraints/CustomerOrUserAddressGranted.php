<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that a customer user address or a customer address
 * is allowed to use for an order.
 */
class CustomerOrUserAddressGranted extends Constraint
{
    /** @var string */
    public $message = 'oro.order.orderaddress.not_allowed';

    /** @var string The type of an order address (billing or shipping) */
    public $addressType;

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return 'oro_order_customer_or_user_address_granted';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
