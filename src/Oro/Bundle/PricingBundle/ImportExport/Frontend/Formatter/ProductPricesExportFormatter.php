<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

/**
 * Converts product tier prices in specific format for frontend product export.
 */
class ProductPricesExportFormatter
{
    private NumberFormatter $numberFormatter;

    private UnitValueFormatterInterface $unitValueFormatter;

    private UnitLabelFormatterInterface $unitLabelFormatter;

    public function __construct(
        NumberFormatter $numberFormatter,
        UnitValueFormatterInterface $unitValueFormatter,
        UnitLabelFormatterInterface $unitLabelFormatter
    ) {
        $this->numberFormatter = $numberFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
    }

    /**
     * @param array|ProductPriceInterface[] $prices
     * @return string
     */
    public function formatTierPrices(array $prices): string
    {
        if (empty($prices)) {
            return '';
        }

        return implode(PHP_EOL, array_map(fn ($price) => $this->formatTierPrice($price), $prices));
    }

    private function formatTierPrice(ProductPriceInterface $price): string
    {
        return sprintf(
            '%s | %s',
            $this->numberFormatter->formatCurrency($price->getPrice()->getValue(), $price->getPrice()->getCurrency()),
            $this->unitValueFormatter->formatCode($price->getQuantity(), $price->getUnit()->getCode())
        );
    }

    public function formatPriceAttribute(PriceAttributeProductPrice $priceAttributeProductPrice): string
    {
        $price = $priceAttributeProductPrice->getPrice();
        if (!$price) {
            return '';
        }

        return sprintf(
            '%s / %s',
            $this->numberFormatter->formatCurrency($price->getValue(), $price->getCurrency()),
            $this->unitLabelFormatter->format($priceAttributeProductPrice->getProductUnitCode())
        );
    }

    public function formatPrice(ProductPriceInterface $price): string
    {
        return sprintf(
            '%s / %s',
            $this->numberFormatter->formatCurrency($price->getPrice()->getValue(), $price->getPrice()->getCurrency()),
            $this->unitLabelFormatter->format($price->getUnit()->getCode())
        );
    }
}
