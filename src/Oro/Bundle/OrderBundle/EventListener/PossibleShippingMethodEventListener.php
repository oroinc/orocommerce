<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class PossibleShippingMethodEventListener
{
    const CALCULATE_SHIPPING_KEY = 'calculateShipping';
    const POSSIBLE_SHIPPING_METHODS_KEY = 'possibleShippingMethods';

    /**
     * @var ShippingContextFactoryInterface
     */
    protected $factory;

    /**
     * @var ShippingPriceProviderInterface|null
     */
    protected $priceProvider;

    /**
     * @var ShippingPricesConverter
     */
    protected $priceConverter;

    public function __construct(
        ShippingContextFactoryInterface $factory,
        ShippingPricesConverter $priceConverter,
        ShippingPriceProviderInterface $priceProvider = null
    ) {
        $this->factory = $factory;
        $this->priceConverter = $priceConverter;
        $this->priceProvider = $priceProvider;
    }

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
