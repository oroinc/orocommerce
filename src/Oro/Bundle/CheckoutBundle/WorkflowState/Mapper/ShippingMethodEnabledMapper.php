<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodEnabledMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipping_method_enabled';

    /**
     * @var ShippingPriceProvider
     */
    protected $shippingPriceProvider;

    /**
     * @var CheckoutShippingContextFactory
     */
    protected $shippingContextFactory;

    /**
     * @param ShippingPriceProvider          $shippingPriceProvider
     * @param CheckoutShippingContextFactory $shippingContextFactory
     */
    public function __construct(
        ShippingPriceProvider $shippingPriceProvider,
        CheckoutShippingContextFactory $shippingContextFactory
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->shippingContextFactory = $shippingContextFactory;
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
        if (!$entity->getShippingMethod()) {
            return true;
        }

        $shippingMethod = $entity->getShippingMethod();
        if ($shippingMethod) {
            return null !== $this->shippingPriceProvider->getPrice(
                $this->shippingContextFactory->create($entity),
                $shippingMethod,
                $entity->getShippingMethodType()
            );
        }

        return false;
    }
}
