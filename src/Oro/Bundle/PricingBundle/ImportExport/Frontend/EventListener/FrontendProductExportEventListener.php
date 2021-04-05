<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Frontend\EventListener;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter\ProductExportPricesFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesExportProvider;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportDataConverterEvent;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportNormalizerEvent;

/**
 * Include product prices to frontend product listing export.
 */
class FrontendProductExportEventListener
{
    private const TIER_PRICES_FIELD_NAME = 'tier_prices';

    private FrontendProductPricesExportProvider $productPricesExportProvider;
    private ProductExportPricesFormatter $productPriceFormatter;

    public function __construct(
        FrontendProductPricesExportProvider $productPricesExportProvider,
        ProductExportPricesFormatter $productPriceFormatter
    ) {
        $this->productPricesExportProvider = $productPricesExportProvider;
        $this->productPriceFormatter = $productPriceFormatter;
    }

    /**
     * @param ProductExportDataConverterEvent $event
     */
    public function onConvertToExport(ProductExportDataConverterEvent $event): void
    {
        $backendHeaders = $event->getBackendHeaders();
        $rules = $event->getHeaderRules();

        if ($this->productPricesExportProvider->isPriceAttributesExportEnabled()) {
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

    /**
     * @param ProductExportNormalizerEvent $event
     */
    public function onProductExportNormalize(ProductExportNormalizerEvent $event): void
    {
        $product = $event->getProduct();
        $options = $event->getOptions();
        $data = $event->getData();

        if ($this->productPricesExportProvider->isPriceAttributesExportEnabled()) {
            $prices = $this->productPricesExportProvider->getProductPrices($product, $options);

            $data = array_merge($data, $prices);
        }

        if ($this->productPricesExportProvider->isTierPricesExportEnabled()) {
            $tierPrices = $this->productPricesExportProvider->getTierPrices($product, $options);

            $data[self::TIER_PRICES_FIELD_NAME] = $this->productPriceFormatter->formatTierPrices($tierPrices);
        }

        $event->setData($data);
    }
}
