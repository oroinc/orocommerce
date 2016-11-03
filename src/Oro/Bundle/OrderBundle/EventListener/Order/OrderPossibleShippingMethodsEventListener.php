<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class OrderPossibleShippingMethodsEventListener
{
    const CALCULATE_SHIPPING_KEY = 'calculateShipping';
    const POSSIBLE_SHIPPING_METHODS_KEY = 'possibleShippingMethods';

    /**
     * @var OrderShippingContextFactory
     */
    protected $factory;

    /**
     * @var ShippingPriceProvider|null
     */
    protected $priceProvider;

    /**
     * @param OrderShippingContextFactory $factory
     * @param ShippingPriceProvider|null $priceProvider
     */
    public function __construct(
        OrderShippingContextFactory $factory,
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
        if (array_key_exists(self::CALCULATE_SHIPPING_KEY, $event->getSubmittedData()) &&
            $event->getSubmittedData()[self::CALCULATE_SHIPPING_KEY] === 'true'
        ) {
            if (!$this->priceProvider) {
                $data = [];
            } else {
                $order = $event->getOrder();

                $context = $this->factory->create($order);
                $data = $this->priceProvider->getApplicableMethodsWithTypesData($context, true);
            }
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}
