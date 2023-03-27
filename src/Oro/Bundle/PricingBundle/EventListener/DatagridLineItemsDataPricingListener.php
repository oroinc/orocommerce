<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedDTO;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

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

    public function __construct(
        SubtotalProviderInterface $lineItemNotPricedSubtotalProvider,
        RoundingServiceInterface $roundingService,
        NumberFormatter $numberFormatter
    ) {
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->roundingService = $roundingService;
        $this->numberFormatter = $numberFormatter;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $subtotal = $this->lineItemNotPricedSubtotalProvider
            ->getSubtotal(new LineItemsNotPricedDTO(new ArrayCollection($lineItems)));
        if ($subtotal === null) {
            return;
        }

        $subtotalData = (array) $subtotal->getData();

        foreach ($lineItems as $lineItem) {
            $lineItemSubtotalData = $this->getLineItemSubtotalData($lineItem, $subtotalData);

            $priceValue = $this->getPriceValueForLineItem($lineItem, $lineItemSubtotalData);
            $subtotalValue = $this->getSubtotalValueForLineItem($lineItem, $lineItemSubtotalData);
            $currency = (string)$subtotal->getCurrency();

            $event->addDataForLineItem(
                (int)$lineItem->getEntityIdentifier(),
                [
                    self::PRICE_VALUE => $priceValue,
                    self::CURRENCY => $currency,
                    self::SUBTOTAL_VALUE => $subtotalValue,
                    self::PRICE => $priceValue !== null
                        ? $this->numberFormatter->formatCurrency($priceValue, $currency)
                        : null,
                    self::SUBTOTAL => $subtotalValue !== null
                        ? $this->numberFormatter->formatCurrency($subtotalValue, $currency)
                        : null,
                ]
            );
        }
    }

    private function getLineItemSubtotalData(ProductLineItemInterface $lineItem, array $subtotalData): array
    {
        $lineItemHash = spl_object_hash($lineItem);

        return $subtotalData[$lineItemHash] ?? [];
    }

    private function getPriceValueForLineItem(ProductLineItemInterface $lineItem, array $lineItemSubtotalData): ?float
    {
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
            $priceValue = $this->getPriceValueForLineItem($lineItem, $lineItemSubtotalData);
            if ($priceValue !== null) {
                // The logic of multiplying and rounding is mimicking {@see LineItemNotPricedSubtotalProvider}.
                $subtotalAmount = BigDecimal::of($priceValue)
                    ->multipliedBy((float)$lineItem->getQuantity());

                return $this->roundingService->round($subtotalAmount->toFloat());
            }
        }

        return $lineItemSubtotalData[LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL] ?? null;
    }
}
