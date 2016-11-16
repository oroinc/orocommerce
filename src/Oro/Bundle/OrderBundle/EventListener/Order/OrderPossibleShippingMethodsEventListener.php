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
    public function __construct(OrderShippingContextFactory $factory, ShippingPriceProvider $priceProvider = null)
    {
        $this->factory = $factory;
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $submittedData = $event->getSubmittedData();
        if (array_key_exists(self::CALCULATE_SHIPPING_KEY, $submittedData) || count($submittedData) === 0) {
            $data = [];
            if ($this->priceProvider) {
                $data = $this->priceProvider
                    ->getApplicableMethodsWithTypesData($this->factory->create($event->getOrder()));
            }
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}
