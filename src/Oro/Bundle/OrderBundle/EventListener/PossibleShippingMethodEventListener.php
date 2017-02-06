<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;

class PossibleShippingMethodEventListener
{
    const CALCULATE_SHIPPING_KEY = 'calculateShipping';
    const POSSIBLE_SHIPPING_METHODS_KEY = 'possibleShippingMethods';

    /**
     * @var ShippingContextFactoryInterface
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
     * @param ShippingContextFactoryInterface $factory
     * @param ShippingPricesConverter $priceConverter
     * @param ShippingPriceProvider|null $priceProvider
     */
    public function __construct(
        ShippingContextFactoryInterface $factory,
        ShippingPricesConverter $priceConverter,
        ShippingPriceProvider $priceProvider = null
    ) {
        $this->factory = $factory;
        $this->priceConverter = $priceConverter;
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param EntityDataAwareEventInterface $event
     */
    public function onEvent(EntityDataAwareEventInterface $event)
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
                $shippingContext = $this->factory->create($event->getEntity());
                $shippingMethodViews = $this->priceProvider
                    ->getApplicableMethodsViews($shippingContext)
                    ->toArray();
                $data = $this->priceConverter->convertPricesToArray($shippingMethodViews);
            }
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}
