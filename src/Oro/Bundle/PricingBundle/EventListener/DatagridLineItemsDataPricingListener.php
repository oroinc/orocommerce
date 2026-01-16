<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitItemLineItemsDataListener;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;

/**
 * Adds line items pricing data.
 */
class DatagridLineItemsDataPricingListener
{
    public const PRICE_VALUE = 'priceValue';
    public const SUBTOTAL_VALUE = 'subtotalValue';
    public const PRICE = 'price';
    public const SUBTOTAL = 'subtotal';
    public const CURRENCY = 'currency';

    private ProductLineItemPriceProviderInterface $productLineItemsPriceProvider;

    private ProductLineItemsHolderFactoryInterface $productLineItemsHolderFactory;

    private RoundingServiceInterface $roundingService;

    private NumberFormatter $numberFormatter;

    public function __construct(
        ProductLineItemPriceProviderInterface $productLineItemsPriceProvider,
        ProductLineItemsHolderFactoryInterface $productLineItemsHolderFactory,
        RoundingServiceInterface $roundingService,
        NumberFormatter $numberFormatter
    ) {
        $this->productLineItemsPriceProvider = $productLineItemsPriceProvider;
        $this->productLineItemsHolderFactory = $productLineItemsHolderFactory;
        $this->roundingService = $roundingService;
        $this->numberFormatter = $numberFormatter;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $lineItemsHolder = $this->productLineItemsHolderFactory->createFromLineItems($lineItems);
        $productLineItemsPrices = $this->productLineItemsPriceProvider
            ->getProductLineItemsPricesForLineItemsHolder($lineItemsHolder);

        foreach ($lineItems as $key => $lineItem) {
            $lineItemPrice = $productLineItemsPrices[$key] ?? null;

            $lineItemId = $lineItem->getEntityIdentifier();
            $lineItemData = $event->getDataForLineItem($lineItemId);
            $this->setPricingData($lineItemData, $lineItem, $lineItemPrice);

            if ($lineItemData[DatagridKitLineItemsDataListener::IS_KIT] ?? false) {
                $kitItemLineItemsData = $lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] ?? [];

                foreach ($kitItemLineItemsData as $index => $kitItemLineItemDatum) {
                    $kitItemLineItem = $kitItemLineItemDatum[DatagridKitItemLineItemsDataListener::ENTITY] ?? null;
                    if (!$kitItemLineItem instanceof ProductKitItemLineItemInterface) {
                        continue;
                    }

                    $hasSubtotal = $this->setPricingData(
                        $lineItemData[DatagridKitLineItemsDataListener::SUB_DATA][$index],
                        $kitItemLineItem,
                        $lineItemPrice?->getKitItemLineItemPrice($kitItemLineItem)
                    );

                    if ($hasSubtotal === false) {
                        $lineItemData[DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR] = true;
                    }
                }
            }

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    private function setPricingData(
        array &$lineItemData,
        ProductLineItemInterface $lineItem,
        ?ProductLineItemPrice $lineItemPrice
    ): bool {
        $currency = $this->getCurrencyForLineItem($lineItem, $lineItemPrice);
        $priceValue = $this->getPriceValueForLineItem($lineItem, $lineItemPrice);
        $subtotalValue = $this->getSubtotalValueForLineItem($lineItem, $lineItemPrice);

        $lineItemData[self::PRICE_VALUE] = $priceValue;
        $lineItemData[self::CURRENCY] = $currency;
        $lineItemData[self::SUBTOTAL_VALUE] = $subtotalValue;
        $lineItemData[self::PRICE] = $priceValue !== null
            ? $this->numberFormatter->formatCurrency($priceValue, $currency)
            : null;
        $lineItemData[self::SUBTOTAL] = $subtotalValue !== null
            ? $this->numberFormatter->formatCurrency($subtotalValue, $currency)
            : null;

        return $subtotalValue !== null && $subtotalValue >= 0;
    }

    private function getCurrencyForLineItem(
        ProductLineItemInterface $lineItem,
        ?ProductLineItemPrice $lineItemPrice
    ): ?string {
        $currency = null;
        if ($lineItem instanceof PriceAwareInterface) {
            $currency = $lineItem->getPrice()?->getCurrency();
        }

        if (!$currency) {
            $currency = $lineItemPrice?->getPrice()?->getCurrency();
        }

        return $currency;
    }

    private function getPriceValueForLineItem(
        ProductLineItemInterface $lineItem,
        ?ProductLineItemPrice $lineItemPrice
    ): ?float {
        $priceValue = null;
        if ($lineItem instanceof PriceAwareInterface) {
            $priceValue = $lineItem->getPrice()?->getValue();
        }

        if (!$priceValue) {
            $priceValue = $lineItemPrice?->getPrice()?->getValue();
        }

        return $priceValue;
    }

    private function getSubtotalValueForLineItem(
        ProductLineItemInterface $lineItem,
        ?ProductLineItemPrice $productLineItemPrice
    ): ?float {
        if ($lineItem instanceof PriceAwareInterface) {
            $price = $lineItem->getPrice();
            if ($price !== null) {
                $subtotalValue = BigDecimal::of($price->getValue())
                    ->multipliedBy((float)$lineItem->getQuantity())
                    ->toFloat();

                return $this->roundingService->round($subtotalValue);
            }
        }

        return $productLineItemPrice?->getSubtotal();
    }
}
