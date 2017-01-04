<?php

namespace Oro\Bundle\SaleBundle\EventListener\Quote;

use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Factory\QuoteShippingContextFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class QuotePossibleShippingMethodsEventListener
{
    const CALCULATE_SHIPPING_KEY = 'calculateShipping';
    const POSSIBLE_SHIPPING_METHODS_KEY = 'possibleShippingMethods';

    /**
     * @var QuoteShippingContextFactory
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
     * @param QuoteShippingContextFactory $factory
     * @param ShippingPricesConverter $priceConverter
     * @param ShippingPriceProvider|null $priceProvider
     */
    public function __construct(
        QuoteShippingContextFactory $factory,
        ShippingPricesConverter $priceConverter,
        ShippingPriceProvider $priceProvider = null
    ) {
        $this->factory = $factory;
        $this->priceConverter = $priceConverter;
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param QuoteEvent $event
     */
    public function onQuoteEvent(QuoteEvent $event)
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
                $shippingContext = $this->factory->create($event->getQuote());
                $shippingMethodViews = $this->priceProvider
                    ->getApplicableMethodsWithTypesData($shippingContext)
                    ->toArray();
                $data = $this->priceConverter->convertPricesToArray($shippingMethodViews);
            }
            $event->getData()->offsetSet(self::POSSIBLE_SHIPPING_METHODS_KEY, $data);
        }
    }
}

