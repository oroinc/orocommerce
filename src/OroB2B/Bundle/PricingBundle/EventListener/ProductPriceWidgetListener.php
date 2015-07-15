<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;

class ProductPriceWidgetListener
{
    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(PriceListRequestHandler $priceListRequestHandler)
    {
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param ProductGridWidgetRenderEvent $event
     */
    public function onWidgetRender(ProductGridWidgetRenderEvent $event)
    {
        $params = $event->getWidgetRouteParameters();
        $params[PriceListRequestHandler::PRICE_LIST_KEY]
            = $this->priceListRequestHandler->getPriceList()->getId();

        $params[PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY]
            = $this->priceListRequestHandler->getPriceListCurrencies();

        $params[PriceListRequestHandler::TIER_PRICES_KEY] = $this->priceListRequestHandler->showTierPrices();

        $event->setWidgetRouteParameters($params);
    }
}
