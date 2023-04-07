<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitItemLineItemsDataListener;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\ShoppingListBundle\Model\Factory\ShoppingListLineItemsHolderFactory;

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

    private SubtotalProviderInterface $subtotalProvider;

    private ShoppingListLineItemsHolderFactory $lineItemsHolderFactory;

    private RoundingServiceInterface $roundingService;

    private NumberFormatter $numberFormatter;

    public function __construct(
        SubtotalProviderInterface $subtotalProvider,
        ShoppingListLineItemsHolderFactory $lineItemsHolderFactory,
        RoundingServiceInterface $roundingService,
        NumberFormatter $numberFormatter
    ) {
        $this->subtotalProvider = $subtotalProvider;
        $this->lineItemsHolderFactory = $lineItemsHolderFactory;
        $this->roundingService = $roundingService;
        $this->numberFormatter = $numberFormatter;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $lineItemsHolder = $this->lineItemsHolderFactory->createFromLineItems($lineItems);
        $subtotal = $this->subtotalProvider->getSubtotal($lineItemsHolder);
        if (!$subtotal) {
            return;
        }

        $currency = $subtotal->getCurrency();

        foreach ($lineItems as $lineItem) {
            $lineItemSubtotal = $subtotal->getLineItemSubtotal($lineItem);

            $lineItemId = $lineItem->getEntityIdentifier();
            $lineItemData = $event->getDataForLineItem($lineItemId);
            $this->setPricingData($lineItemData, $lineItem, $currency, $lineItemSubtotal);

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
                        $currency,
                        $lineItemSubtotal?->getLineItemSubtotal($kitItemLineItem)
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

    private function setPricingData(
        array &$lineItemData,
        ProductLineItemInterface $lineItem,
        string $currency,
        ?Subtotal $lineItemSubtotal
    ): bool {
        $priceValue = $this->getPriceValueForLineItem($lineItem, $lineItemSubtotal);
        $subtotalValue = $this->getSubtotalValueForLineItem($lineItem, $lineItemSubtotal);

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
        ?Subtotal $lineItemSubtotal
    ): ?float {
        $priceValue = null;
        if ($lineItem instanceof PriceAwareInterface) {
            $priceValue = $lineItem->getPrice()?->getValue();
        }

        if (!$priceValue) {
            $priceValue = $lineItemSubtotal?->getPrice()?->getValue();
        }

        return $priceValue;
    }

    private function getSubtotalValueForLineItem(
        ProductLineItemInterface $lineItem,
        ?Subtotal $lineItemSubtotal
    ): ?float {
        if ($lineItem instanceof PriceAwareInterface) {
            $priceValue = $lineItem->getPrice()?->getValue();
            if ($priceValue !== null) {
                // The logic of multiplying and rounding is mimicking {@see LineItemNotPricedSubtotalProvider}.
                $subtotalAmount = BigDecimal::of($priceValue)
                    ->multipliedBy((float)$lineItem->getQuantity());

                return $this->roundingService->round($subtotalAmount->toFloat());
            }
        }

        return $lineItemSubtotal?->getAmount();
    }
}
