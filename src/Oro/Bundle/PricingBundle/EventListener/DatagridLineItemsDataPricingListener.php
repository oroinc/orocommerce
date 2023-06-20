<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitItemLineItemsDataListener;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactory;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener;

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

    private SubtotalProviderInterface $lineItemNotPricedSubtotalProvider;

    private RoundingServiceInterface $roundingService;

    private NumberFormatter $numberFormatter;

    private ProductLineItemsHolderFactoryInterface $productLineItemsHolderFactory;

    public function __construct(
        SubtotalProviderInterface $lineItemNotPricedSubtotalProvider,
        RoundingServiceInterface $roundingService,
        NumberFormatter $numberFormatter
    ) {
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->roundingService = $roundingService;
        $this->numberFormatter = $numberFormatter;
        $this->productLineItemsHolderFactory = new ProductLineItemsHolderFactory();
    }

    public function setProductLineItemsHolderFactory(
        ProductLineItemsHolderFactoryInterface $productLineItemsHolderFactory
    ): void {
        $this->productLineItemsHolderFactory = $productLineItemsHolderFactory;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $lineItemsHolder = $this->productLineItemsHolderFactory->createFromLineItems($lineItems);
        $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotal($lineItemsHolder);
        if ($subtotal === null) {
            return;
        }

        $subtotalData = (array)$subtotal->getData();
        $currency = (string)$subtotal->getCurrency();

        foreach ($lineItems as $lineItem) {
            $lineItemSubtotalData = $this->getLineItemSubtotalData($lineItem, $subtotalData);
            $lineItemId = $lineItem->getEntityIdentifier();
            $lineItemData = $event->getDataForLineItem($lineItemId);
            $this->setPricingData($lineItemData, $lineItem, $lineItemSubtotalData, $currency);

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
                        $this->getLineItemSubtotalData($kitItemLineItem, $subtotalData),
                        $currency
                    );

                    if ($hasSubtotal === false
                        && empty($lineItemData[DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR])) {
                        $lineItemData[DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR] = true;
                    }
                }
            }

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    private function getLineItemSubtotalData(ProductLineItemInterface $lineItem, array $subtotalData): array
    {
        $lineItemHash = spl_object_hash($lineItem);

        return $subtotalData[$lineItemHash] ?? [];
    }

    private function setPricingData(
        array &$lineItemData,
        ProductLineItemInterface $lineItem,
        array $lineItemSubtotalData,
        string $currency
    ): bool {
        $priceValue = $this->getPriceValueForLineItem($lineItem, $lineItemSubtotalData);
        $subtotalValue = $this->getSubtotalValueForLineItem($lineItem, $lineItemSubtotalData);

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

    private function getPriceValueForLineItem(
        ProductLineItemInterface $lineItem,
        array $lineItemSubtotalData
    ): ?float {
        $priceValue = null;
        if ($lineItem instanceof PriceAwareInterface) {
            $priceValue = $lineItem->getPrice()?->getValue();
        }

        if (!$priceValue) {
            $priceValue = $lineItemSubtotalData[LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE] ?? null;
        }

        return $priceValue;
    }

    private function getSubtotalValueForLineItem(
        ProductLineItemInterface $lineItem,
        array $lineItemSubtotalData
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

        return $lineItemSubtotalData[LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL] ?? null;
    }
}
