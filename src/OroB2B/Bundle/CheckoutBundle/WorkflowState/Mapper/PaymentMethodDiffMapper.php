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
     * {@inheritdoc}
     */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     * @return string
     */
    public function getCurrentState($checkout)
    {
        return $checkout->getPaymentMethod();
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        if (isset($savedState[$this->getName()]) &&
            empty($savedState[$this->getName()]) && empty($checkout->getPaymentMethod())
        ) {
            return true;
        }

        if (!isset($savedState[$this->getName()]) ||
            !is_string($savedState[$this->getName()])
        ) {
            return false;
        }

        $paymentMethod = $savedState[$this->getName()];

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
