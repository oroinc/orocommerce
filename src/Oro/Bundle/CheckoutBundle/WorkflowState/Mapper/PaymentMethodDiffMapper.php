<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class PaymentMethodDiffMapper implements CheckoutStateDiffMapperInterface
{
    public const DATA_NAME = 'payment_method';

    #[\Override]
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    #[\Override]
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     */
    #[\Override]
    public function getCurrentState($checkout)
    {
        return $checkout->getPaymentMethod();
    }

    #[\Override]
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }
}
