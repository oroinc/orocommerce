<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodEnabledMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'payment_method_enabled';

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var PaymentContextProvider */
    protected $paymentContextProvider;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     * @param PaymentContextProvider $paymentContextProvider
     */
    public function __construct(
        PaymentMethodRegistry $paymentMethodRegistry,
        PaymentContextProvider $paymentContextProvider
    ) {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
        $this->paymentContextProvider = $paymentContextProvider;
    }

    /** {@inheritdoc} */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /** {@inheritdoc} */
    public function getCurrentState($entity)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     * @param Checkout $entity
     */
    public function isStatesEqual($entity, $state1, $state2)
    {
        $paymentMethodName = $entity->getPaymentMethod();

        if (!$paymentMethodName) {
            return true;
        }

        try {
            $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodName);
        } catch (\InvalidArgumentException $ex) {
            return false;
        }

        $paymentContext = $this->paymentContextProvider->processContext(['entity' => $entity], $entity);

        return $paymentMethod->isEnabled() && $paymentMethod->isApplicable($paymentContext);
    }
}
