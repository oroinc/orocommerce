<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;

/**
 * Adds pricing data to the LineItemDataBuildEvent.
 */
class LineItemDataBuildListener
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
     * @param LineItemDataBuildEvent $event
     */
    public function onLineItemData(LineItemDataBuildEvent $event): void
    {
        $lineItems = $event->getLineItems();
        $matchedPrices = $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);
        if (!$matchedPrices) {
            return;
        }

        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            /** @var Price $productPrice */
            $productPrice = $matchedPrices[$lineItem->getProduct()->getId()][$lineItem->getProductUnitCode()] ?? null;
            if (!$productPrice) {
                continue;
            }

            $lineItemId = $lineItem->getId();
            $lineItemData = $event->getDataForLineItem($lineItemId);
            $price = $productPrice->getValue();
            $currency = $productPrice->getCurrency();
            $subtotal = $price * $lineItem->getQuantity();

            $lineItemData['price'] = $this->numberFormatter->formatCurrency($price, $currency);
            $lineItemData['subtotal'] = $this->numberFormatter->formatCurrency($subtotal, $currency);
            $lineItemData['currency'] = $currency;
            $lineItemData['subtotalValue'] = $subtotal;

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }
}
