<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Frontend\EventListener;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter\ProductPricesExportFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesExportProvider;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportDataConverterEvent;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportNormalizerEvent;

/**
 * Include product prices to frontend product listing export.
 */
class FrontendProductExportEventListener
{
    private const TIER_PRICES_FIELD_NAME = 'tier_prices';
    private const PRICE_FIELD_NAME = 'price';

    private FrontendProductPricesExportProvider $productPricesExportProvider;

    private ProductPricesExportFormatter $productExportPricesFormatter;

    public function __construct(
        FrontendProductPricesExportProvider $productPricesExportProvider,
        ProductPricesExportFormatter $productPriceFormatter
    ) {
        $this->productPricesExportProvider = $productPricesExportProvider;
        $this->productExportPricesFormatter = $productPriceFormatter;
    }

    public function onConvertToExport(ProductExportDataConverterEvent $event): void
    {
        $backendHeaders = $event->getBackendHeaders();
        $rules = $event->getHeaderRules();

        if ($this->productPricesExportProvider->isPricesExportEnabled()) {
            $rules[self::PRICE_FIELD_NAME] = self::PRICE_FIELD_NAME;
            $backendHeaders[] = self::PRICE_FIELD_NAME;

            $availableAttributes = $this->productPricesExportProvider->getAvailableExportPriceAttributes();

            /** @var PriceAttributePriceList $priceAttribute */
            foreach ($availableAttributes as $priceAttribute) {
                $fieldName = $priceAttribute->getFieldName();
                $rules[$fieldName] = $fieldName;
                $backendHeaders[] = $fieldName;
            }
        }

        if ($this->productPricesExportProvider->isTierPricesExportEnabled()) {
            $rules[self::TIER_PRICES_FIELD_NAME] = self::TIER_PRICES_FIELD_NAME;
            $backendHeaders[] = self::TIER_PRICES_FIELD_NAME;
        }

        $event->setBackendHeaders($backendHeaders);
        $event->setHeaderRules($rules);
    }

    public function onProductExportNormalize(ProductExportNormalizerEvent $event): void
    {
        $product = $event->getProduct();
        $options = $event->getOptions();
        $data = $event->getData();

        if ($this->productPricesExportProvider->isPricesExportEnabled()) {
            $productPrice = $this->productPricesExportProvider->getProductPrice($product, $options);
            $data[self::PRICE_FIELD_NAME] = $productPrice
                ? $this->productExportPricesFormatter->formatPrice($productPrice)
                : '';

            $priceAttributePrices = $this->productPricesExportProvider
                ->getProductPriceAttributesPrices($product, $options);
            foreach ($priceAttributePrices as $priceAttributeProductPrice) {
                $data[$priceAttributeProductPrice->getPriceList()->getFieldName()]
                    = $this->productExportPricesFormatter->formatPriceAttribute($priceAttributeProductPrice);
            }
        }

        if ($this->productPricesExportProvider->isTierPricesExportEnabled()) {
            $tierPrices = $this->productPricesExportProvider->getTierPrices($product, $options);

            $data[self::TIER_PRICES_FIELD_NAME] = $this->productExportPricesFormatter->formatTierPrices($tierPrices);
        }

        $event->setData($data);
    }
}
