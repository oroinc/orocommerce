<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\Validator\Constraints\CheckoutShipUntil;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks that a checkout object is valid.
 */
class CreateOrderCheckUpcomingListener
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function onBeforeOrderCreate(ExtendableConditionEvent $event): void
    {
        /** @var Checkout $checkout */
        $checkout = $event->getContext()->getEntity();
        if ($this->validator->validate($checkout, new CheckoutShipUntil())->count()) {
            $event->addError('');
        }
    }
}
