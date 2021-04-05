<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

/**
 * Converts product tier prices in specific format for frontend product export.
 */
class ProductExportPricesFormatter
{
    private NumberFormatter $numberFormatter;
    private UnitValueFormatterInterface $unitValueFormatter;

    public function __construct(NumberFormatter $numberFormatter, UnitValueFormatterInterface $unitValueFormatter)
    {
        $this->numberFormatter = $numberFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
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

        return implode(PHP_EOL, array_map(function ($priceDto) {
            return $this->formatPrice($priceDto);
        }, current($prices)));
    }

    /**
     * @param ProductPriceInterface $price
     * @return string
     */
    private function formatPrice(ProductPriceInterface $price): string
    {
        return sprintf(
            '%s | %s',
            $this->numberFormatter->formatCurrency($price->getPrice()->getValue(), $price->getPrice()->getCurrency()),
            $this->unitValueFormatter->formatCode($price->getQuantity(), $price->getUnit()->getCode())
        );
    }
}
