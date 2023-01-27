<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * Adds possible shipping methods to submitted data for such entities as orders or quotes.
 */
class PossibleShippingMethodEventListener
{
    public const CALCULATE_SHIPPING_KEY = 'calculateShipping';
    public const POSSIBLE_SHIPPING_METHODS_KEY = 'possibleShippingMethods';

    private ShippingContextFactoryInterface $factory;
    private ShippingPriceProviderInterface $priceProvider;
    private ShippingPricesConverter $priceConverter;

    public function __construct(
        ShippingContextFactoryInterface $factory,
        ShippingPricesConverter $priceConverter,
        ShippingPriceProviderInterface $priceProvider
    ) {
        $this->factory = $factory;
        $this->priceConverter = $priceConverter;
        $this->priceProvider = $priceProvider;
    }

    public function onEvent(EntityDataAwareEventInterface $event): void
    {
        $submittedData = $event->getSubmittedData();
        if ($submittedData === null
            || (
                \array_key_exists(self::CALCULATE_SHIPPING_KEY, $submittedData)
                && $submittedData[self::CALCULATE_SHIPPING_KEY]
            )
        ) {
            $shippingContext = $this->factory->create($event->getEntity());
            $shippingMethodViews = $this->priceProvider->getApplicableMethodsViews($shippingContext)->toArray();
            $data = $this->priceConverter->convertPricesToArray($shippingMethodViews);
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}
