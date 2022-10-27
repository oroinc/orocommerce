<?php

namespace Oro\Bundle\PricingBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

/**
 * Provides a functionality to format product prices.
 */
class ProductPriceFormatter
{
    private NumberFormatter $numberFormatter;
    private UnitLabelFormatterInterface $unitLabelFormatter;
    private UnitValueFormatterInterface $unitValueFormatter;

    public function __construct(
        NumberFormatter $numberFormatter,
        UnitLabelFormatterInterface $unitLabelFormatter,
        UnitValueFormatterInterface $unitValueFormatter
    ) {
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
    }

    public function formatProductPrice(ProductPriceInterface $price): array
    {
        $priceValue = $price->getPrice()->getValue();
        $priceCurrency = $price->getPrice()->getCurrency();
        $unitCode = $price->getUnit()->getCode();

        return [
            'price' => $priceValue,
            'currency' => $priceCurrency,
            'quantity' => $price->getQuantity(),
            'unit' => $unitCode,
            'formatted_price' => $this->numberFormatter->formatCurrency($priceValue, $priceCurrency),
            'formatted_unit' => $this->unitLabelFormatter->format($unitCode),
            'quantity_with_unit' => $this->unitValueFormatter->formatCode($price->getQuantity(), $unitCode)
        ];
    }

    /**
     * @param array $productsWithPrices [product id => [unit => [ProductPriceInterface, ...], ...], ...]
     *
     * @return array [product id => ['{unit}_{quantity}' => formatted price (array), ...], ...]
     */
    public function formatProducts(array $productsWithPrices): array
    {
        $resultPrices = [];
        foreach ($productsWithPrices as $productId => $units) {
            $resultPrices[$productId] = $this->formatProductUnits($units);
        }

        return $resultPrices;
    }

    /**
     * @param array $units [unit => [ProductPriceInterface, ...], ...]
     *
     * @return array ['{unit}_{quantity}' => formatted price (array), ...]
     */
    private function formatProductUnits(array $units): array
    {
        $productData = [];
        foreach ($units as $unit => $prices) {
            /** @var ProductPriceInterface $price */
            foreach ($prices as $price) {
                $index = sprintf('%s_%s', $unit, $price->getQuantity());
                $productData[$index] = $this->formatProductPrice($price);
            }
        }

        return $productData;
    }
}
