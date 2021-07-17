<?php

namespace Oro\Bundle\PricingBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

/**
 * Used to create formatted array of ProductPriceInterface variables
 */
class ProductPriceFormatter
{
    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var UnitLabelFormatterInterface
     */
    protected $unitLabelFormatter;

    /**
     * @var UnitValueFormatterInterface
     */
    protected $unitValueFormatter;

    public function __construct(
        NumberFormatter $numberFormatter,
        UnitLabelFormatterInterface $unitLabelFormatter,
        UnitValueFormatterInterface $unitValueFormatter
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
     * @param ProductPriceInterface $price
     * @return array
     */
    public function formatProductPrice(ProductPriceInterface $price)
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
     * @param array $units
     * @return array
     */
    private function formatProductUnits(array $units)
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
