<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
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

    private FrontendProductPricesDataProvider $frontendProductPricesDataProvider;

    private UserCurrencyManager $userCurrencyManager;

    private NumberFormatter $numberFormatter;

    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        UserCurrencyManager $userCurrencyManager,
        NumberFormatter $numberFormatter
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->numberFormatter = $numberFormatter;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        $matchedPrices = $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);
        $currency = $this->userCurrencyManager->getUserCurrency();

        foreach ($lineItems as $lineItem) {
            $productPrice = $this->getPrice($lineItem, $matchedPrices);
            $priceValue = $productPrice?->getValue();
            $subtotalValue = $priceValue !== null ? $priceValue * (float)$lineItem->getQuantity() : null;

            $event->addDataForLineItem(
                (int) $lineItem->getEntityIdentifier(),
                [
                    self::PRICE_VALUE => $priceValue,
                    self::CURRENCY => $currency,
                    self::SUBTOTAL_VALUE => $subtotalValue,
                    self::PRICE => $priceValue !== null
                        ? $this->numberFormatter->formatCurrency($priceValue, $currency)
                        : null,
                    self::SUBTOTAL => $subtotalValue
                        ? $this->numberFormatter->formatCurrency($subtotalValue, $currency)
                        : null,
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
