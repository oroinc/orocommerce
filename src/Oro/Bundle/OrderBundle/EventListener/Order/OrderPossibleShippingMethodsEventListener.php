<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderPossibleShippingMethodsEventListener
{
    const POSSIBLE_SHIPPING_METHODS_KEY = 'possibleShippingMethods';

    /**
     * @var ShippingContextProviderFactory
     */
    protected $factory;

    /**
     * @var ShippingPriceProvider|null
     */
    protected $priceProvider;

    /**
     * @param ShippingContextProviderFactory $factory
     * @param ShippingPriceProvider|null $priceProvider
     */
    public function __construct(
        ShippingContextProviderFactory $factory,
        ShippingPriceProvider $priceProvider = null
    ) {
        $this->factory = $factory;
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        if ($this->priceProvider) {
            $order = $event->getOrder();

            $context = $this->factory->create($order);
            $data = $this->priceProvider->getApplicableMethodsWithTypesData($context, true);

            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}
