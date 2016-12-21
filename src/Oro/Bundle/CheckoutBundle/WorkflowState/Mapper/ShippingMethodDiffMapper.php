<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipping_method';

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
        $shippingMethod = $checkout->getShippingMethod();
        if ($shippingMethod) {
            $shippingMethodType = $checkout->getShippingMethodType();
            $price = $this->shippingPriceProvider->getPrice(
                $this->shippingContextFactory->create($checkout),
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
        if (($state2 === '' && $state1 !== '') || !$entity->getShippingMethod()) {
            return true;
        }

        return $state1 === $state2;
    }
}
