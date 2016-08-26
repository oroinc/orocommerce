<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Provider\PaymentContextProvider;

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
        // This mapper doesn't generate current state
        // Availability of payment method is calculated by `isStatesEqual` on fly
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

        $paymentContext = $this->paymentContextProvider->processContext($entity);

        return $paymentMethod->isEnabled() && $paymentMethod->isApplicable($paymentContext);
    }
}
