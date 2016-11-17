<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\CurrencyBundle\Entity\Price;
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
        if ($submittedData === null || array_key_exists(self::CALCULATE_SHIPPING_KEY, $submittedData)) {
            $data = [];
            if ($this->priceProvider) {
                $data = $this->priceProvider
                    ->getApplicableMethodsWithTypesData($this->factory->create($event->getOrder()));
                $data = $this->convertPricesToArray($data);
            }
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function convertPricesToArray(array $data)
    {
        return array_map(function ($methodData) {
            $methodData['types'] = array_map(function ($typeData) {
                /** @var Price $price */
                $price = $typeData['price'];
                $typeData['price'] = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency(),
                ];
                return $typeData;
            }, $methodData['types']);
            return $methodData;
        }, $data);
    }
}
