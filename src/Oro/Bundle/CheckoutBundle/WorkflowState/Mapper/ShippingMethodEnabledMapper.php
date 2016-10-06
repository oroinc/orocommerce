<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodEnabledMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipping_method_enabled';

    /**
     * @var ShippingPriceProvider
     */
    protected $shippingPriceProvider;

    /**
     * @var ShippingContextProviderFactory
     */
    protected $shippingContextProviderFactory;

    /**
     * @param ShippingPriceProvider $shippingPriceProvider
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingPriceProvider $shippingPriceProvider,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
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

    /** {@inheritdoc} */
    public function isStatesEqual($entity, $state1, $state2)
    {
        if (!$entity->getShippingMethod()) {
            return true;
        }


        $shippingMethod = $entity->getShippingMethod();
        if ($shippingMethod) {
            $shippingContext = $this->shippingContextProviderFactory->create($entity);
            $allMethodsData = $this->shippingPriceProvider->getApplicableMethodsWithTypesData($shippingContext);

            if (array_key_exists($entity->getShippingMethod(), $allMethodsData)) {
                $method = $allMethodsData[$entity->getShippingMethod()];
                foreach ($method['types'] as $type) {
                    if (array_key_exists('identifier', $type)
                        && $type['identifier'] === $entity->getShippingMethodType()
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
