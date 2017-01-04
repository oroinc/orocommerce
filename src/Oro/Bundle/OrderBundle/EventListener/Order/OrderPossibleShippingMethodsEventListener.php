<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
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
     * @var ShippingPricesConverter
     */
    protected $priceConverter;

    /**
     * @param OrderShippingContextFactory $factory
     * @param ShippingPricesConverter $priceConverter
     * @param ShippingPriceProvider|null $priceProvider
     */
    public function __construct(
        OrderShippingContextFactory $factory,
        ShippingPricesConverter $priceConverter,
        ShippingPriceProvider $priceProvider = null
    ) {
        $this->factory = $factory;
        $this->priceConverter = $priceConverter;
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $submittedData = $event->getSubmittedData();
        if ($submittedData === null
            || (
                array_key_exists(self::CALCULATE_SHIPPING_KEY, $submittedData)
                && $submittedData[self::CALCULATE_SHIPPING_KEY]
            )
        ) {
            $data = [];
            if ($this->priceProvider) {
                $shippingContext = $this->factory->create($event->getOrder());
                $shippingMethodViews = $this->priceProvider
                    ->getApplicableMethodsWithTypesData($shippingContext)
                    ->toArray();
                $data = $this->priceConverter->convertPricesToArray($shippingMethodViews);
            }
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}
