<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class PaymentMethodDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'paymentMethod';

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(PaymentMethodRegistry $paymentMethodRegistry)
    {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }


    /**
     * @return int
     */
    public function getPriority()
    {
        return 40;
    }

    /**
     * @param object $entity
     * @return boolean
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof Checkout;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    public function getCurrentState($checkout)
    {
        return [
            self::DATA_NAME => $checkout->getPaymentMethod(),
        ];
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function compareStates($checkout, array $savedState)
    {
        if (!isset($savedState[self::DATA_NAME]) ||
            !is_string($savedState[self::DATA_NAME])
        ) {
            return false;
        }

        $paymentMethod = $savedState[self::DATA_NAME];

        try {
            if (!$this->paymentMethodRegistry->getPaymentMethod($paymentMethod)->isEnabled()) {
                return false;
            }
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return $paymentMethod === $checkout->getPaymentMethod();
    }
}
