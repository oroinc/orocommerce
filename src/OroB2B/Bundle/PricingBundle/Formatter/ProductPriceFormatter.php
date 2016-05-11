<?php

namespace OroB2B\Bundle\PricingBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductPriceFormatter
{
    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $unitLabelFormatter;

    /**
     * @var ProductUnitValueFormatter
     */
    protected $unitValueFormatter;

    /**
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitLabelFormatter $unitLabelFormatter
     * @param ProductUnitValueFormatter $unitValueFormatter
     */
    public function __construct(
        NumberFormatter $numberFormatter,
        ProductUnitLabelFormatter $unitLabelFormatter,
        ProductUnitValueFormatter $unitValueFormatter
    ) {
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
    }

    /**
     * @param array $productsWithPrices
     * @return array
     */
    public function formatProducts(array $productsWithPrices)
    {
        $resultPrices = [];
        foreach ($productsWithPrices as $productId => $units) {
            $resultPrices[$productId] = $this->formatProductUnits($units);
        }

        return $resultPrices;
    }

    /**
     * @param array $units
     * @return array
     */
    public function formatProductUnits(array $units)
    {
        $productData = [];
        foreach ($units as $unit => $pricesData) {
            foreach ($pricesData as $priceData) {
                $quantity = $priceData['qty'];
                $price = $priceData['price'];
                $index = sprintf('%s_%s', $unit, $quantity);
                $currencyIsoCode = $priceData['currency'];
                $productData[$index] = [
                    'price' => $price,
                    'currency' => $currencyIsoCode,
                    'formatted_price' => $this->numberFormatter->formatCurrency($price, $currencyIsoCode),
                    'unit' => $unit,
                    'formatted_unit' => $this->unitLabelFormatter->format($unit),
                    'quantity' => $quantity,
                    'quantity_with_unit' => $this->unitValueFormatter->formatCode($quantity, $unit)
                ];
            }
        }
        return $productData;
    }
}
