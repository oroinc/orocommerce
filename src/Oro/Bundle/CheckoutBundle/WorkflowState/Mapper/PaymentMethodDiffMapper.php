<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Maps payment method changes for checkout state diff tracking.
 *
 * Tracks changes to the payment method selected in a checkout, enabling detection of
 * payment method modifications during the checkout workflow.
 */
class PaymentMethodDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'payment_method';

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
