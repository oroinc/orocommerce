<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;

class ShippingMethodEnabledMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipping_method_enabled';

    /**
     * @var CheckoutShippingMethodsProviderInterface
     */
    private $checkoutShippingMethodsProvider;

    public function __construct(CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider)
    {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
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
        // This mapper doesn't generate current state
        // Availability of payment method is calculated by `isStatesEqual` on fly
        return '';
    }

    /**
     * @param Checkout $entity
     * @param mixed $state1
     * @param mixed $state2
     * @return bool
     */
    public function isStatesEqual($entity, $state1, $state2)
    {
        $shippingMethod = $entity->getShippingMethod();

        if (!$shippingMethod) {
            return true;
        }

        if ($shippingMethod) {
            return null !== $this->checkoutShippingMethodsProvider->getPrice($entity);
        }

        return false;
    }
}
