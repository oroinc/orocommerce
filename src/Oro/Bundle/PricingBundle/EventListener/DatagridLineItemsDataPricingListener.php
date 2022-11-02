<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Adds line items pricing data.
 */
class DatagridLineItemsDataPricingListener
{
    /** @var FrontendProductPricesDataProvider */
    private $frontendProductPricesDataProvider;

    /** @var NumberFormatter */
    private $numberFormatter;

    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->numberFormatter = $numberFormatter;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        $matchedPrices = $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);

        foreach ($lineItems as $lineItem) {
            $productPrice = $this->getPrice($lineItem, $matchedPrices);
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

    private function getPrice(ProductLineItemInterface $lineItem, array $matchedPrices): ?Price
    {
        $productPrice = null;
        if ($lineItem instanceof PriceAwareInterface) {
            $productPrice = $lineItem->getPrice();
        }

        if (!$productPrice && $lineItem->getProduct()) {
            $productPrice = $matchedPrices[$lineItem->getProduct()->getId()][$lineItem->getProductUnitCode()] ?? null;
        }

        return $productPrice;
    }
}
