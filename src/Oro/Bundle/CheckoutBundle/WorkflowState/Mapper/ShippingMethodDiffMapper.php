<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipping_method';

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
        $shippingMethod = $checkout->getShippingMethod();
        if ($shippingMethod) {
            $shippingMethodType = $checkout->getShippingMethodType();
            $price = $this->shippingPriceProvider->getPrice(
                $this->shippingContextProviderFactory->create($checkout),
                $shippingMethod,
                $shippingMethodType
            );
            if ($price) {
                return md5(serialize([
                    'method' => $shippingMethod,
                    'type' => $shippingMethodType,
                    'price' => $price,
                ]));
            }
        }

        return '';
    }

    /** {@inheritdoc} */
    public function isStatesEqual($entity, $state1, $state2)
    {
        if (!$entity->getShippingMethod()) {
            return true;
        }

        return $state1 === $state2;
    }
}
