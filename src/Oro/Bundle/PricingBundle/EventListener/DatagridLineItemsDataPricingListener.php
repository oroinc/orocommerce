<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds line items pricing data.
 */
class DatagridLineItemsDataPricingListener
{
    /** @var FrontendProductPricesDataProvider */
    private $frontendProductPricesDataProvider;

    /** @var NumberFormatter */
    private $numberFormatter;

    /**
     * @param FrontendProductPricesDataProvider $frontendProductPricesDataProvider
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param DatagridLineItemsDataEvent $event
     */
    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        $matchedPrices = $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);
        if (!$matchedPrices) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            /** @var Price $productPrice */
            $productPrice = $matchedPrices[$lineItem->getProduct()->getId()][$lineItem->getProductUnitCode()] ?? null;
            if (!$productPrice) {
                continue;
            }

            $price = $productPrice->getValue();
            $currency = $productPrice->getCurrency();
            $subtotal = $price * $lineItem->getQuantity();

            $event->addDataForLineItem(
                $lineItem->getId(),
                [
                    'price' => $this->numberFormatter->formatCurrency($price, $currency),
                    'subtotal' => $this->numberFormatter->formatCurrency($subtotal, $currency),
                    'currency' => $currency,
                    'subtotalValue' => $subtotal,
                ]
            );
        }
    }
}
